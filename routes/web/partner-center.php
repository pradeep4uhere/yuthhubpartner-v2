<?php

use App\Http\Controllers\PartnerCenterPortalController;
use Illuminate\Support\Facades\Route;

Route::prefix('partner-center')->name('partner-center.')->group(function (): void {

    // Partner Center login page.
    Route::get('/login', [PartnerCenterPortalController::class, 'create'])
        ->middleware('guest:partner_center')
        ->name('login');

    // Authenticate Partner Center.
    Route::post('/login', [PartnerCenterPortalController::class, 'store'])
        ->middleware(['guest:partner_center', 'throttle:5,1'])
        ->name('login.store');

    Route::middleware('auth:partner_center')->group(function (): void {

        // Partner Center dashboard.
        Route::get('/dashboard', [PartnerCenterPortalController::class, 'dashboard'])
            ->name('dashboard');

        // Partner Center logout.
        Route::post('/logout', [PartnerCenterPortalController::class, 'destroy'])
            ->name('logout');
    });
});
