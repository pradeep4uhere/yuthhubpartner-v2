<?php

use App\Http\Controllers\PartnerPlacementPortalController;
use Illuminate\Support\Facades\Route;

Route::prefix('partner-placement')->name('partner-placement.')->group(function (): void {

    // Placement Partner login page.
    Route::get('/login', [PartnerPlacementPortalController::class, 'create'])
        ->middleware('guest:partner_placement')
        ->name('login');

    // Authenticate Placement Partner.
    Route::post('/login', [PartnerPlacementPortalController::class, 'store'])
        ->middleware(['guest:partner_placement', 'throttle:5,1'])
        ->name('login.store');

    Route::middleware('auth:partner_placement')->group(function (): void {

        // Display Placement Partner MPIN verification page.
        Route::get('/mpin/verify', [
            PartnerPlacementPortalController::class,
            'showMpinVerification',
        ])->name('mpin.verify');

        // Verify Placement Partner MPIN.
        Route::post('/mpin/verify', [
            PartnerPlacementPortalController::class,
            'verifyMpin',
        ])->name('mpin.verify.submit');

        // Placement Partner dashboard.
        Route::get('/dashboard', [
            PartnerPlacementPortalController::class,
            'dashboard',
        ])->name('dashboard');

        // Placement Partner logout.
        Route::post('/logout', [
            PartnerPlacementPortalController::class,
            'destroy',
        ])->name('logout');
    });
});
