<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItems;
use App\Models\Company;
use App\Models\Meal;
use App\Models\Order;
use App\Models\OrderItems;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramRegistrationController extends Controller
{
    protected $telegramApiUrl;

    public function __construct()
    {
        $this->telegramApiUrl = "https://api.telegram.org/bot" . env('TELEGRAM_BOT_TOKEN') . "/";
    }

    private function getChatId($data)
    {
        if (isset($data['message']['chat']['id'])) {
            return $data['message']['chat']['id'];
        } elseif (isset($data['callback_query']['message']['chat']['id'])) {
            return $data['callback_query']['message']['chat']['id'];
        }
        return null;
    }

    private function getChatData($data)
    {
        if (isset($data['message']['text'])) {

            return $data['message']['text'];
        } elseif (isset($data['callback_query']['data'])) {

            return $data['callback_query']['data'];
        } elseif (isset($data['message']['photo'])) {

            return 'photo';
        }

        return 'other';
    }

    private function getStep(int $chatId, string $data)
    {
        $data = strtolower($data);
        if ($data == 'start' || $data == '/start' || !(cache()->get("registration_step_{$chatId}"))) {
            cache()->forget("registration_step_as_{$chatId}");
            cache()->forget("registration_step_{$chatId}");
            cache()->forget("registration_employee_{$chatId}");
            return cache()->get("registration_step_{$chatId}", 'start');
        }
        return cache()->get("registration_step_{$chatId}");
    }

    private function sendOptions(int $chatId, ?string $text = null)
    {
        if (!$text) {
            $text = 'Please choose one of the options to registration, please: ';
            $keyboards = [
                [
                    ['text' => 'As Company', 'callback_data' => "company"],
                    ['text' => 'Company Employee', 'callback_data' => "employee"],
                ]
            ];
        } else {
            $keyboards = [];
        }
        Http::post($this->telegramApiUrl . 'sendMessage', [
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboards,
                'resize_keyboard' => true,
                'one_time_keyboard' => true,
            ]),
        ]);
    }

    private function sendMessage(int $chatId, string $text)
    {
        Http::post($this->telegramApiUrl . 'sendMessage', [
            'chat_id' => $chatId,
            'text' => $text,
        ]);
    }

    private function sendCompanyInfo(int $chatId, $company)
    {
        Http::post($this->telegramApiUrl . 'sendMessage', [
            'chat_id' => $chatId,
            'text' => "Company's name: {$company['name']}\n" .
                "Longitude: {$company['longitude']}\n" .
                "Latitude: {$company['latitude']}\n",
        ]);
    }

    private function getFile($fileId)
    {
        $url = $this->telegramApiUrl . "getFile";
        $response = Http::post($url, ['file_id' => $fileId]);
        return $response->json();
    }

    private function getPhotoAndStore($chatId, $data)
    {
        $photoArr = end($data['message']['photo']) ?? null;
        $photoInfo = $this->getFile($photoArr['file_id']);
        $fileUrl = "https://api.telegram.org/file/bot" . env('TELEGRAM_BOT_TOKEN') . "/{$photoInfo['result']['file_path']}";
        $uniqId = uniqid();
        $photoPath = public_path("images/{$uniqId}.jpg");
        $fileContent = file_get_contents($fileUrl);
        file_put_contents($photoPath, $fileContent);

        cache()->put("photo_path_{$chatId}", "images/$uniqId.jpg");
    }

    private function deleteMessage($chatId, $messageId)
    {
        Http::post($this->telegramApiUrl . 'deleteMessage', [
            'chat_id' => $chatId,
            'message_id' => $messageId
        ]);
    }

    private function getMessageId($data)
    {
        if (isset($data['message']['message_id'])) {
            return $data['message']['message_id'];
        } elseif (isset($data['callback_query']['message']['message_id'])) {
            return $data['callback_query']['message']['message_id'];
        }
    }

    private function sendCompaniesToUser(int $chatId, $companies)
    {
        $keyboards = [];
        foreach ($companies as $company) {
            $keyboards[] = [
                ['text' => "{$company->name}", 'callback_data' => "company_id:$company->id"],
            ];
        }
        // Log::info([$keyboards]);
        Http::post($this->telegramApiUrl . "sendMessage", [
            'chat_id' => $chatId,
            'text' => "Company names ",
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboards,
                'resize_keyboard' => true,
                'one_time_keyboard' => true,
            ]),
        ]);
    }

    private function sendMessageMeal($chatId, $mealName)
    {
        // Log::info('Man sho\'rda');
        Http::post($this->telegramApiUrl . 'sendMessage', [
            'chat_id' => $chatId,
            'text' => 'Enter the count of ' . $mealName . ' portion : ',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => 'Back 🔙', 'callback_data' => 'back'],
                    ]
                ],
                'resize_keyboard' => true,
                'one_time_keyboard' => true,
            ]),
        ]);
    }

    private function sendMenuAgain($chatId, $menu, ?array $additionalKeyboard)
    {
        if ($additionalKeyboard) {
            $menu = array_merge($menu, array_chunk($additionalKeyboard, 2));
        }

        Http::post($this->telegramApiUrl . 'sendMessage', [
            'chat_id' => $chatId,
            'text' => 'Today\'s menu(Please, make your order until 11 am 😉):',
            'reply_markup' => json_encode([
                'inline_keyboard' => $menu,
                'resize_keyboard' => true,
                'one_time_keyboard' => true,
            ]),
        ]);
    }

    private function sendCart($chatId, $cartItems)
    {
        foreach ($cartItems as $cartItem) {
            // Log::info([$cartItem->meal->name]);
            $name = $cartItem->meal->name;
            $count = $cartItem->count;
            Http::post($this->telegramApiUrl . "sendMessage", [
                'chat_id' => $chatId,
                'text' => "Name: {$name}\nCount: {$count}",
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [
                            ['text' => 'Delete', 'callback_data' => "delete:{$cartItem->id}"],
                            ['text' => 'Edit portion', 'callback_data' => "edit:{$cartItem->id}"],
                        ]
                    ],
                    'resize_keyboard' => true,
                    'one_time_keyboard' => true,
                ])
            ]);
        }
        Http::post($this->telegramApiUrl . "sendMessage", [
            'chat_id' => $chatId,
            'text' => "Confirm order or go back to the menu",
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => 'Confirm Order', 'callback_data' => "confirm"],
                        ['text' => 'Back to Menu', 'callback_data' => "menu"],
                    ]
                ],
                'resize_keyboard' => true,
                'one_time_keyboard' => true,
            ])
        ]);
    }



    public function handle(Request $request)
    {
        $data = $request->all();

        $chatId = $this->getChatId($data);

        $chatData = $this->getChatData($data);

        $step = $this->getStep($chatId, $chatData);

        $messageId = $this->getMessageId($data);

        $user = User::where('chat_id', $chatId)->first();

        $additionToKeyboard = [
            ['text' => "Show cart 🛒", 'callback_data' => "cart"],
            ['text' => "Order 🛵", 'callback_data' => "order"]
        ];

        if (isset($data['callback_query']['data']) && strpos($data['callback_query']['data'], ":")) {
            $keyboards = $data['callback_query']['message']['reply_markup']['inline_keyboard'];
        }
        // Log::info([$data]);

        if (!$user) {
            switch ($step) {
                case 'start':
                case '/start':
                    $this->sendOptions($chatId);
                    $this->deleteMessage($chatId, $messageId);
                    cache()->put("registration_step_{$chatId}", 'registration');
                    break;

                case 'registration':
                    $regStepAs = cache()->get("registration_step_as_{$chatId}");
                    // Log::info([$regStepAs, $data]);

                    if ($chatData == 'company' || $regStepAs == 'company') {

                        $this->deleteMessage($chatId, $messageId - 1);
                        $this->deleteMessage($chatId, $messageId);

                        cache()->put("registration_step_as_{$chatId}", 'company');

                        $regStepComp = cache()->get("registration_company_{$chatId}", 'start');

                        switch ($regStepComp) {
                            case 'start':

                                $this->sendMessage($chatId, "Please, enter a name for your company: ");

                                cache()->put("registration_company_{$chatId}", 'name');

                                break;
                            case 'name':

                                $this->deleteMessage($chatId, $messageId - 1);
                                $this->deleteMessage($chatId, $messageId);

                                cache()->put("company_name_{$chatId}", $chatData);

                                $this->sendMessage($chatId, "Please, send a photo for your company!");

                                cache()->put("registration_company_{$chatId}", 'logo');

                                break;
                            case 'logo':

                                if ($chatData == 'photo') {

                                    $this->deleteMessage($chatId, $messageId - 1);
                                    $this->deleteMessage($chatId, $messageId);

                                    $this->getPhotoAndStore($chatId, $data);

                                    $this->sendMessage($chatId, "Now, please send your company's location longitude(Uzunlik):");

                                    cache()->put("registration_company_{$chatId}", 'longitude');
                                } else {
                                    $this->deleteMessage($chatId, $messageId - 1);
                                    $this->deleteMessage($chatId, $messageId);
                                    $this->sendMessage($chatId, "Please, send a photo for your company, nothing else!😌");
                                }
                                break;
                            case 'longitude':

                                $this->deleteMessage($chatId, $messageId - 1);
                                $this->deleteMessage($chatId, $messageId);

                                cache()->put("company_longitude_{$chatId}", $chatData);

                                $this->sendMessage($chatId, "Now, please send your company's location latitude(Kenglik):");

                                cache()->put("registration_company_{$chatId}", 'latitude');

                                break;
                            case 'latitude':

                                $this->deleteMessage($chatId, $messageId - 1);
                                $this->deleteMessage($chatId, $messageId);

                                $this->sendMessage($chatId, "Your company has been created. Now you need to register for yourself!");

                                $company = Company::create([
                                    'name' => cache()->get("company_name_{$chatId}"),
                                    'logo' => cache()->get("photo_path_{$chatId}"),
                                    'longitude' => cache()->get("company_longitude_{$chatId}"),
                                    'latitude' => $chatData
                                ]);
                                $company = Company::where('id', $company->id)->first();
                                $this->sendCompanyInfo($chatId, $company);

                                cache()->put("created_company_id_{$chatId}", $company->id);

                                cache()->forget("registration_company_{$chatId}");

                                $regStepAs = 'employee';

                                cache()->put("registration_step_as_{$chatId}", 'employee');

                                break;
                        }
                    }
                    if ($chatData == 'employee' || $regStepAs == 'employee') {

                        $regStepAs = cache()->put("registration_step_as_{$chatId}", 'employee');

                        $regStepEmployee = cache()->get("registration_employee_{$chatId}", 'start');

                        // Log::info([$regStepAs, $chatData, $regStepEmployee]);

                        switch ($regStepEmployee) {
                            case 'start':

                                $this->sendMessage($chatId, "Please, enter your name: ");

                                cache()->put("registration_employee_{$chatId}", 'name');

                                break;
                            case 'name':

                                $this->deleteMessage($chatId, $messageId - 1);
                                $this->deleteMessage($chatId, $messageId);

                                cache()->put("employee_name_{$chatId}", $chatData);

                                $this->sendMessage($chatId, "Please, send a email!");

                                cache()->put("registration_employee_{$chatId}", 'email');

                                break;
                            case 'email':

                                $this->sendMessage($chatId, "Please, send password");
                                cache()->put("employee_email_{$chatId}", $chatData);

                                cache()->put("registration_employee_{$chatId}", 'password');

                                break;

                                $this->deleteMessage($chatId, $messageId - 1);
                                $this->deleteMessage($chatId, $messageId);

                                break;
                            case 'password':

                                $company = '';
                                $id = '';
                                if (strpos($chatData, ":")) {
                                    list($company, $id) = explode(":", $chatData);
                                    Log::info([$company, $id]);
                                }
                                if (cache()->get("created_company_id_{$chatId}") !== null) {

                                    User::create([
                                        'name' => cache()->get("employee_name_{$chatId}"),
                                        'email' => cache()->get("employee_email_{$chatId}"),
                                        'password' => Hash::make($chatData),
                                        'role' => 'company_admin',
                                        'company_id' => cache()->get("created_company_id_{$chatId}"),
                                        'chat_id' => $chatId
                                    ]);
                                    cache()->forget("registration_employee_{$chatId}");
                                } elseif ($company == 'company_id' && $id) {
                                    User::create([
                                        'name' => cache()->get("employee_name_{$chatId}"),
                                        'email' => cache()->get("employee_email_{$chatId}"),
                                        'password' => Hash::make($chatData),
                                        'company_id' => $id,
                                        'chat_id' => $chatId
                                    ]);
                                } else {

                                    $this->sendMessage($chatId, "Your should choose one of this companies!");
                                    $companies = Company::where('status', 1)->get();
                                    $this->sendCompaniesToUser($chatId, $companies);
                                    break;
                                }
                                $this->sendMessage($chatId, "Your user account also has been created!:)");


                                $this->deleteMessage($chatId, $messageId - 1);
                                $this->deleteMessage($chatId, $messageId);

                                break;
                        }
                    }
                    break;
            }
        }
        $mealEdit = cache()->get("cart_meal_portion_edit_{$chatId}", []);

        if ($mealEdit) {

            CartItems::where('id', $mealEdit)->update([
                'count' => $chatData
            ]);

            $this->sendMenuAgain($chatId, cache()->get('menu_keyboards'), $additionToKeyboard);

            cache()->forget("cart_meal_portion_edit_{$chatId}");
        }

        if (stripos($chatData, ":") || $mealEdit) {

            list($key, $id) = explode(":", $chatData);

            switch ($key) {
                case 'meal_id':
                    $this->deleteMessage($chatId, $messageId);
                    $this->sendMessageMeal($chatId, Meal::where('id', $id)->first()->name);

                    cache()->put("meal_to_cart_{$chatId}", $id);

                    break;
                case 'edit':

                    cache()->put("cart_meal_portion_edit_{$chatId}", $id);

                    $this->deleteMessage($chatId, $messageId);

                    $this->sendMessageMeal($chatId, CartItems::where('id', $id)->first()->meal->name);

                    break;
                case 'delete':


                    $this->deleteMessage($chatId, $messageId);
                    $this->deleteMessage($chatId, $messageId - 1);

                    CartItems::where('id', $id)->delete();

                    $this->sendMenuAgain($chatId, cache()->get('menu_keyboards'), $additionToKeyboard);
                    
                    break;
            }
        } else {

            switch ($chatData) {
                case 'back':
                    cache()->forget("meal_to_cart_{$chatId}");
                    $this->deleteMessage($chatId, $messageId);
                    $this->sendMenuAgain($chatId, cache()->get('menu_keyboards'), []);
                    break;
                case 'cart':
                    $this->sendCart($chatId, Cart::where('user_id', User::where('chat_id', $chatId)->first()->id)->first()->cartItems);
                    break;
                case 'order':
                    $tempCartId = cache()->get("cart_{$chatId}");
                    $tempCart = Cart::where('id', $tempCartId)->first();

                    $order = Order::create([
                        'company_id' => $tempCart->user->company_id,
                        'user_id' => $tempCart->user_id,
                        'summ' => $tempCart->summ,
                        'date' => now()->format('Y-m-d')
                    ]);

                    foreach ($tempCart->cartItems as $temp) {
                        OrderItems::create([
                            'order_id' => $order->id,
                            'meal_id' => $temp->meal_id,
                            'count' => $temp->count
                        ]);
                    }

                    $temp->delete();
                    $this->sendMessage($chatId, "Your order has been recorded! :)");
                    break;
                case 'menu':
                    $this->sendMenuAgain($chatId, cache()->get('menu_keyboards'), $additionToKeyboard);
                    break;
                default:

                    $cartFor = cache()->get("cart_{$chatId}", []);

                    if (!$cartFor) {



                        $this->deleteMessage($chatId, $messageId);
                        $this->deleteMessage($chatId, $messageId - 1);

                        $cartEx = Cart::where('date', now()->format('Y-m-d'))->where('user_id', User::where('chat_id', $chatId)->first()->id)->first();

                        if (!$cartEx) {
                            $cart = Cart::create([
                                'name' => "something",
                                'user_id' => User::where('chat_id', $chatId)->first()->id,
                                'date' => now()->format('Y-m-d'),
                                'summ' => 0
                            ]);

                            CartItems::create([
                                'cart_id' => $cart->id,
                                'meal_id' => cache()->get("meal_to_cart_{$chatId}"),
                                'count' => $chatData
                            ]);

                            cache()->put("cart_{$chatId}", $cart->id);
                            cache()->forget("meal_to_cart_{$chatId}");

                            $this->sendMenuAgain($chatId, cache()->get('menu_keyboards'), $additionToKeyboard);
                        } else {
                            $this->sendMessage($chatId, "You have ordered for today");
                        }
                    } else {

                        CartItems::updateOrCreate(
                            ['cart_id' => cache()->get("cart_{$chatId}"), 'meal_id' => cache()->get("meal_to_cart_{$chatId}"),],
                            ['count' => $chatData]
                        );

                        $this->deleteMessage($chatId, $messageId);
                        $this->deleteMessage($chatId, $messageId - 1);

                        $this->sendMenuAgain($chatId, cache()->get('menu_keyboards'), $additionToKeyboard);
                    }

                    break;
            }
        }
    }
}
