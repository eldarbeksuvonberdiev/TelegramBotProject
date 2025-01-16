<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
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
        }
        return null;
    }

    private function getStep(int $chatId, string $data)
    {
        $data = strtolower($data);
        if ($data == 'start' || $data == '/start') {
            cache()->forget("registration_step_{$chatId}");
            return cache()->get("registration_step_{$chatId}", 'start');
        }
        return cache()->get("registration_step_{$chatId}");
    }

    public function handle(Request $request)
    {
        $chatId = $this->getChatId($request->all());
        $chatData = $this->getChatData($request->all());
        $step = $this->getStep($chatId, $chatData);
        Log::info([$chatId, $chatData, $step, $request->all()]);

        switch ($step) {
            case 'start':
            case '/start':
                Http::post($this->telegramApiUrl . 'sendMessage', [
                    'chat_id' => $chatId,
                    'text' => 'Please choose an option below:',
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [
                                ['text' => 'Accept ✅', 'callback_data' => "accept:{$chatId}"],
                                ['text' => 'Reject ❌', 'callback_data' => "reject:{$chatId}"],
                            ]
                        ],
                        'resize_keyboard' => true,
                        'one_time_keyboard' => true,
                    ]),
                ]);
                break;

            default:
                Http::post($this->telegramApiUrl . 'sendMessage', [
                    'chat_id' => $chatId,
                    'text' => 'Please choose an option below:',
                    'reply_markup' => json_encode([
                        'keyboard' => [
                            [
                                ['text' => 'Active'],
                                ['text' => 'Inactive'],
                            ]
                        ],
                        'resize_keyboard' => true,
                    ]),
                ]);
                Http::post($this->telegramApiUrl . 'sendMessage', [
                    'chat_id' => $chatId,
                    'text' => 'Please choose an option below:',
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [
                                ['text' => 'Accept ✅', 'callback_data' => "accept:{$chatId}"],
                                ['text' => 'Reject ❌', 'callback_data' => "reject:{$chatId}"],
                            ]
                        ],
                        'resize_keyboard' => true,
                        'one_time_keyboard' => true,
                    ]),
                ]);
                break;
        }
    }
}
