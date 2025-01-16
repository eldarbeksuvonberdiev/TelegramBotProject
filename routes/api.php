<?php

use App\Http\Controllers\TelegramRegistrationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/registration', [TelegramRegistrationController::class, 'handle']);
