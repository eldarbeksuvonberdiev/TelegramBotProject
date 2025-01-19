<?php

use App\Http\Controllers\MealController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/meal',[MealController::class, 'index'])->name('meal');
    Route::get('/meal-create',[MealController::class, 'create'])->name('meal.create');
    Route::post('/meal-store',[MealController::class, 'store'])->name('meal.store');
    Route::post('/meal-to-cart/{meal}',[MealController::class, 'addToCart'])->name('meal.toCart');
    Route::get('/meal-cart',[MealController::class, 'cart'])->name('meal.cart');
    Route::post('/meal-send',[MealController::class, 'send'])->name('meal.send');
});

require __DIR__.'/auth.php';
