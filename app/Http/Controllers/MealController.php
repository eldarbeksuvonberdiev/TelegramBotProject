<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Meal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MealController extends Controller
{

    protected $telegramApiUrl;

    public function __construct()
    {
        $this->telegramApiUrl = "https://api.telegram.org/bot" . env('TELEGRAM_BOT_TOKEN') . "/";
    }

    public function index()
    {
        $meals = Meal::all();
        return view('meal.meal', compact('meals'));
    }

    public function create()
    {
        return view('meal.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required',
            'price' => 'required'
        ]);

        Meal::create($data);
        return redirect()->route('meal');
    }


    public function addToCart(Meal $meal)
    {
        $cart = session()->get('cart', []);

        $cart[$meal->id] = ['name' => $meal->name];

        session()->put('cart', $cart);

        return back();
    }

    public function cart()
    {
        $companies = Company::where('status', 1)->get();
        return view('meal.cart', compact('companies'));
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'companies' => 'required|exists:companies,id'
        ]);
        $users = User::where('role', 'company_admin')->whereIn('company_id', $data['companies'])->get();
        $cart = session()->get('cart', []);
        if ($cart) {
            foreach ($users as $user) {
                $this->sendMenu($user->chat_id, $cart);
            }
            session()->forget('cart');
        } else {
            return back()->with('message', 'Please, add some meal to cart first! :)');
        }

        return redirect()->route('meal')->with('message', 'Today\' menu has been sent to companies! :)');
    }

    protected function sendMenu($chatId, $menu)
    {
        $keyboards = [];

        foreach ($menu as $id => $value) {

            $keyboards[] =
                ['text' => "{$value['name']}", 'callback_data' => "meal_id:$id"];
        }

        $keyboard = array_merge(array_chunk($keyboards, 3));

        cache()->put('menu_keyboards', $keyboard);

        $response = Http::post($this->telegramApiUrl . 'sendMessage', [
            'chat_id' => $chatId,
            'text' => 'Today\'s menu(Please, make your order until 11 am 😉):',
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard,
                'resize_keyboard' => true,
                'one_time_keyboard' => true,
            ]),
        ]);
        // Log::info([$keyboard, $chatId, $response]);
    }
}
