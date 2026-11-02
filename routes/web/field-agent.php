<?php

use App\Http\Controllers\FieldAgentPortalController;
use Illuminate\Support\Facades\Route;

Route::prefix('field-agent')->name('field-agent.')->group(function (): void {

    // Field Agent login page.
    Route::get('/login', [FieldAgentPortalController::class, 'create'])
        ->middleware('guest:field_agent')
        ->name('login');

    // Authenticate Field Agent.
    Route::post('/login', [FieldAgentPortalController::class, 'store'])
        ->middleware(['guest:field_agent', 'throttle:5,1'])
        ->name('login.store');

    Route::middleware('auth:field_agent')->group(function (): void {

        // Display Field Agent MPIN verification page.
        Route::get('/mpin-verification', [
            FieldAgentPortalController::class,
            'showMpinVerification',
        ])->middleware('throttle:10,1')->name('mpin.verify');

        // Verify Field Agent MPIN.
        Route::post('/mpin-verification', [
            FieldAgentPortalController::class,
            'verifyMpin',
        ])->middleware('throttle:5,1')->name('mpin.verify.submit');

        // Field Agent dashboard.
        Route::get('/dashboard', [FieldAgentPortalController::class, 'dashboard'])
            ->name('dashboard');

        // Field Agent logout.
        Route::post('/logout', [FieldAgentPortalController::class, 'destroy'])
            ->name('logout');
    });
});
