<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SupportTicketController;

Route::middleware(['web', 'auth:partner_center'])
    ->prefix('partner-center')
    ->name('api.partner-center.')
    ->group(function (): void {

        /*
        |--------------------------------------------------------------------------
        | Help & Support
        |--------------------------------------------------------------------------
        */

        // Create support ticket for authenticated Partner Center.
        Route::post('/support-tickets', [SupportTicketController::class, 'store'])
            ->name('support-tickets.store');
    });
