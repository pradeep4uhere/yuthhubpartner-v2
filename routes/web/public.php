<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\LanguageController;
use Illuminate\Support\Facades\Route;

// Public website homepage.
Route::get('/', HomeController::class)->name('home');

// Update website language.
Route::post('/language', [LanguageController::class, 'update'])
    ->name('language.update');
