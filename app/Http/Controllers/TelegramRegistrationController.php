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

    public function handle(Request $request)
    {
        $chatId = $this->getChatId($request->all());
        $chatData = $this->getChatData($request->all());
        $step = $this->getStep($chatId, $chatData);
        Log::info([$chatId, $chatData, $step, $request->all()]);

        switch ($step) {
            case 'start':
            case '/start':
                $this->sendOptions($chatId);
                cache()->put("registration_step_{$chatId}", 'registration');
                break;

            case 'registration':

                $regStep = cache()->get("registration_step_as_{$chatId}");

                if ($chatData == 'company' || $regStep == 'company') {
                    cache()->put("registration_step_as_{$chatId}", 'company');

                    $this->sendMessage($chatId, "Ok");
                } elseif ($chatData == 'employee' || $regStep == 'employee') {

                    cache()->put("registration_step_as_{$chatId}", 'employee');

                    $this->sendMessage($chatId, "not ok");
                }
                break;
            default:
                //
                break;
        }
    }
}
