<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
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
            cache()->forget("registration_step_{$chatId}");
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

    public function handle(Request $request)
    {
        $data = $request->all();

        $chatId = $this->getChatId($data);

        $chatData = $this->getChatData($data);

        $step = $this->getStep($chatId, $chatData);

        $messageId = $this->getMessageId($data);

        // Log::info([$chatId, $chatData, $data, $step, $messageId]);

        switch ($step) {
            case 'start':
            case '/start':
                $this->sendOptions($chatId);
                $this->deleteMessage($chatId, $messageId);
                cache()->put("registration_step_{$chatId}", 'registration');
                break;

            case 'registration':

                $regStepAs = cache()->get("registration_step_as_{$chatId}");

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

                            cache()->forget("registration_company_{$chatId}");
                            cache()->put("registration_step_as_{$chatId}", 'employee');

                            break;
                    }
                } elseif ($chatData == 'employee' || $regStepAs == 'employee') {

                    cache()->put("registration_step_as_{$chatId}", 'employee');

                    $this->sendMessage($chatId, "not ok");
                }
                break;
        }
    }
}
