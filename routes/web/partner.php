<?php

use App\Http\Controllers\PartnerPortalController;
use Illuminate\Support\Facades\Route;

Route::prefix('partner')->name('partner.')->group(function (): void {

    // Partner login page.
    Route::get('/login', [PartnerPortalController::class, 'create'])
        ->middleware('guest:partner')
        ->name('login');

    // Authenticate Partner.
    Route::post('/login', [PartnerPortalController::class, 'store'])
        ->middleware(['guest:partner', 'throttle:5,1'])
        ->name('login.store');

    Route::middleware('auth:partner')->group(function (): void {

        // Display Partner MPIN verification page.
        Route::get('/mpin-verification', [
            PartnerPortalController::class,
            'showMpinVerification',
        ])->middleware('throttle:10,1')->name('mpin.verify');

        // Verify Partner MPIN.
        Route::post('/mpin-verification', [
            PartnerPortalController::class,
            'verifyMpin',
        ])->middleware('throttle:5,1')->name('mpin.verify.submit');

        // Partner dashboard.
        Route::get('/dashboard', [PartnerPortalController::class, 'dashboard'])
            ->name('dashboard');

        // Partner logout.
        Route::post('/logout', [PartnerPortalController::class, 'destroy'])
            ->name('logout');
    });
});
