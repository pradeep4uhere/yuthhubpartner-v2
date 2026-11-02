<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\SupportTicketController;
use App\Http\Controllers\Api\PlacementPartner\DashboardApiController;
use App\Http\Controllers\Api\PlacementPartner\LearnerApiController;
use App\Http\Controllers\Api\PlacementPartner\EventTransactionApiController;
use App\Http\Controllers\Api\PlacementPartner\PlacementPartnerFieldAgentController;
use App\Http\Controllers\Api\PlacementPartner\PlacementPartnerMpinSettingsController;
use App\Http\Controllers\Api\PlacementPartner\PlacementPartnerChangePasswordController;

Route::middleware(['web', 'auth:partner_placement'])
    ->prefix('partner-placement')
    ->name('api.partner-placement.')
    ->group(function (): void {

        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */

        Route::get('/dashboard/summary', [DashboardApiController::class, 'summary'])
            ->name('dashboard.summary');


        /*
        |--------------------------------------------------------------------------
        | Learners
        |--------------------------------------------------------------------------
        */

        Route::get('/learners', [LearnerApiController::class, 'index'])
            ->name('learners.index');


        /*
        |--------------------------------------------------------------------------
        | Event Transactions
        |--------------------------------------------------------------------------
        */

        Route::get('/event-transactions', [EventTransactionApiController::class, 'index'])
            ->name('event-transactions.index');

        Route::post('/event-transactions/export', [EventTransactionApiController::class, 'export'])
            ->name('event-transactions.export');

        // Export status must be registered before the dynamic transaction ID.
        Route::get('/event-transactions/exports/{exportId}', [EventTransactionApiController::class, 'exportStatus'])
            ->whereNumber('exportId')->name('event-transactions.export-status');

        Route::get('/event-transactions/{id}', [EventTransactionApiController::class, 'show'])
            ->whereNumber('id')->name('event-transactions.show');


        /*
        |--------------------------------------------------------------------------
        | Field Agents
        |--------------------------------------------------------------------------
        */

        Route::get('/field-agents/filters', [PlacementPartnerFieldAgentController::class, 'filters'])
            ->name('field-agents.filters');

        Route::get('/field-agents', [PlacementPartnerFieldAgentController::class, 'index'])
            ->name('field-agents.index');

        Route::post('/field-agents/export', [PlacementPartnerFieldAgentController::class, 'export'])
            ->name('field-agents.export');

        Route::get('/field-agents/{id}', [PlacementPartnerFieldAgentController::class, 'fieldAgentDetails'])
            ->whereNumber('id')->name('field-agents.details');

        Route::get('/field-agents/{id}/transactions', [PlacementPartnerFieldAgentController::class, 'fieldAgentDetails'])
            ->whereNumber('id')->name('field-agents.transactions');

        Route::get('/field-agents/{id}/transactions/export', [PlacementPartnerFieldAgentController::class, 'exportFieldAgentTransactions'])
            ->whereNumber('id')->name('field-agents.transactions.export');


        /*
        |--------------------------------------------------------------------------
        | Change Password
        |--------------------------------------------------------------------------
        */

        Route::post('/change-password', [PlacementPartnerChangePasswordController::class, 'update'])
            ->name('change-password');


        /*
        |--------------------------------------------------------------------------
        | MPIN Settings
        |--------------------------------------------------------------------------
        */

        Route::get('/mpin/settings', [PlacementPartnerMpinSettingsController::class, 'show'])
            ->name('mpin.settings.show');

        Route::put('/mpin/settings', [PlacementPartnerMpinSettingsController::class, 'update'])
            ->name('mpin.settings.update');


        /*
        |--------------------------------------------------------------------------
        | Help & Support
        |--------------------------------------------------------------------------
        */

        Route::get('/support-ticket-categories', [SupportTicketController::class, 'categories'])
            ->name('support-tickets.categories');

        Route::get('/support-tickets', [SupportTicketController::class, 'index'])
            ->name('support-tickets.index');

        Route::post('/support-tickets', [SupportTicketController::class, 'store'])
            ->name('support-tickets.store');

        Route::get('/support-tickets/{ticketId}', [SupportTicketController::class, 'show'])
            ->whereNumber('ticketId')->name('support-tickets.show');

        Route::post('/support-tickets/{ticketId}/replies', [SupportTicketController::class, 'storeReply'])
            ->whereNumber('ticketId')->name('support-tickets.replies.store');

        Route::get('/support-ticket-attachments/{attachmentId}/download', [SupportTicketController::class, 'downloadAttachment'])
            ->whereNumber('attachmentId')->name('support-ticket-attachments.download');
    });
