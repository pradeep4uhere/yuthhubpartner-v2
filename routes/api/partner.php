<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Partner\PartnerDashboardApiController;
use App\Http\Controllers\Api\Partner\EventTransactionController as PartnerEventTransactionController;
use App\Http\Controllers\Api\Partner\PartnerFieldAgentController;
use App\Http\Controllers\Api\Partner\ChangePasswordController as PartnerChangePasswordController;
use App\Http\Controllers\Api\Partner\MpinSettingsController as PartnerMpinSettingsController;
use App\Http\Controllers\Api\Partner\PartnerDownloadController;
use App\Http\Controllers\Api\Partner\PartnerAnnouncementController;
use App\Http\Controllers\Api\SupportTicketController;
use App\Http\Controllers\Api\Partner\AdminAnnouncementController as PartnerAdminAnnouncementController;

Route::middleware(['web', 'auth:partner'])
    ->prefix('partner')
    ->name('api.partner.')
    ->group(function (): void {

        /*
        |--------------------------------------------------------------------------
        | Announcements
        |--------------------------------------------------------------------------
        */

        Route::prefix('announcements')->name('announcements.')->group(function (): void {

            // List partner announcements.
            Route::get('/', [PartnerAnnouncementController::class, 'index'])->name('index');

            // List available Field Agents.
            Route::get('/field-agents', [PartnerAnnouncementController::class, 'fieldAgents'])->name('field-agents');

            // Create announcement.
            Route::post('/', [PartnerAnnouncementController::class, 'store'])->name('store');

            // Show announcement.
            Route::get('/{id}', [PartnerAnnouncementController::class, 'show'])
                ->whereNumber('id')->name('show');

            // Update announcement.
            Route::put('/{id}', [PartnerAnnouncementController::class, 'update'])
                ->whereNumber('id')->name('update');

            // Publish announcement.
            Route::post('/{id}/publish', [PartnerAnnouncementController::class, 'publish'])
                ->whereNumber('id')->name('publish');

            // Download announcement attachment.
            Route::get('/{id}/attachment', [PartnerAnnouncementController::class, 'attachment'])
                ->whereNumber('id')->name('attachment');

            // Delete announcement.
            Route::delete('/{id}', [PartnerAnnouncementController::class, 'destroy'])
                ->whereNumber('id')->name('destroy');
        });



        /*
        |--------------------------------------------------------------------------
        | Admin Announcements
        |--------------------------------------------------------------------------
        */

        Route::prefix('admin-announcements')->name('admin-announcements.')->group(function (): void {

            // List Admin announcements assigned to the authenticated Partner.
            Route::get('/', [PartnerAdminAnnouncementController::class, 'index'])
                ->name('index');

            // Get the unread Admin announcement count.
            Route::get('/unread-count', [PartnerAdminAnnouncementController::class, 'unreadCount'])
                ->name('unread-count');

            // Mark an assigned Admin announcement as read.
            Route::post('/{id}/read', [PartnerAdminAnnouncementController::class, 'markAsRead'])
                ->whereNumber('id')
                ->name('read');

            // Preview an assigned Admin announcement attachment.
            Route::get('/{id}/attachment', [PartnerAdminAnnouncementController::class, 'attachment'])
                ->whereNumber('id')
                ->name('attachment');
        });


        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */

        Route::get('/dashboard/summary', [PartnerDashboardApiController::class, 'summary'])
            ->name('dashboard.summary');

        Route::get('/dashboard/growth-overview', [PartnerDashboardApiController::class, 'growthOverview'])
            ->name('dashboard.growth');

        Route::get('/dashboard/event-transactions-by-type', [PartnerDashboardApiController::class, 'eventTransactionsByType'])
            ->name('dashboard.event-transactions-by-type');

        Route::get('/dashboard/recent-activities', [PartnerDashboardApiController::class, 'recentActivities'])
            ->name('dashboard.recent-activities');

        Route::get('/dashboard/top-10-field-agents', [PartnerDashboardApiController::class, 'top10FieldAgents'])
            ->name('dashboard.top-10-field-agents');

        Route::get('/dashboard/learner-age-distribution', [PartnerDashboardApiController::class, 'learnerAgeDistribution'])
            ->name('dashboard.learner-age-distribution');

        Route::get('/dashboard/event-review-performance', [PartnerDashboardApiController::class, 'eventReviewPerformance'])
            ->name('dashboard.event-transaction-status');

        Route::get('/dashboard/partner-center-earnings', [PartnerDashboardApiController::class, 'partnerCenterEarnings'])
            ->name('dashboard.partner-center-earnings');

        Route::get('/dashboard/learner-registration-growth', [PartnerDashboardApiController::class, 'learnerRegistrationGrowth'])
            ->name('dashboard.learner-registration-growth');


        /*
        |--------------------------------------------------------------------------
        | Learners
        |--------------------------------------------------------------------------
        */

        Route::get('/learners', [PartnerDashboardApiController::class, 'indexLearners'])
            ->name('learners.index');

        Route::post('/learners/export', [PartnerDashboardApiController::class, 'exportLearners'])
            ->name('learners.export');


        /*
        |--------------------------------------------------------------------------
        | Event Transactions
        |--------------------------------------------------------------------------
        */

        Route::get('/event-transactions', [PartnerEventTransactionController::class, 'index'])
            ->name('event-transactions.index');

        Route::get('/event-transactions/export/{exportId}/status', [PartnerEventTransactionController::class, 'exportStatus'])
            ->name('event-transactions.export.status');

        Route::post('/event-transactions/export', [PartnerEventTransactionController::class, 'export'])
            ->name('event-transactions.export');

        Route::get('/event-transactions/{id}', [PartnerEventTransactionController::class, 'show'])
            ->whereNumber('id')->name('event-transactions.show');


        /*
        |--------------------------------------------------------------------------
        | Downloads
        |--------------------------------------------------------------------------
        */

        Route::get('/downloads', [PartnerDownloadController::class, 'index'])
            ->name('downloads.index');

        Route::get('/downloads/{export}/status', [PartnerDownloadController::class, 'status'])
            ->name('downloads.status');

        Route::get('/downloads/{export}/download', [PartnerDownloadController::class, 'download'])
            ->name('downloads.download');


        /*
        |--------------------------------------------------------------------------
        | Field Agents
        |--------------------------------------------------------------------------
        */

        Route::get('/field-agents', [PartnerFieldAgentController::class, 'index'])
            ->name('field-agents.index');

        Route::get('/field-agents/filters', [PartnerFieldAgentController::class, 'filters'])
            ->name('field-agents.filters');

        Route::post('/field-agents/export', [PartnerDashboardApiController::class, 'exportFieldAgents'])
            ->name('field-agents.export');

        Route::get('/field-agents/{id}/transactions', [PartnerFieldAgentController::class, 'fieldAgentDetails'])
            ->whereNumber('id')->name('field-agents.transactions');


        /*
        |--------------------------------------------------------------------------
        | Change Password
        |--------------------------------------------------------------------------
        */

        Route::post('/change-password', [PartnerChangePasswordController::class, 'update'])
            ->name('change-password');


        /*
        |--------------------------------------------------------------------------
        | MPIN Settings
        |--------------------------------------------------------------------------
        */

        Route::get('/mpin/settings', [PartnerMpinSettingsController::class, 'show'])
            ->name('mpin.settings.show');

        Route::put('/mpin/settings', [PartnerMpinSettingsController::class, 'update'])
            ->name('mpin.settings.update');


        /*
        |--------------------------------------------------------------------------
        | Help & Support
        |--------------------------------------------------------------------------
        */

        Route::get('/support-ticket-categories', [SupportTicketController::class, 'categories'])
            ->name('support-ticket-categories.index');

        Route::get('/support-tickets', [SupportTicketController::class, 'index'])
            ->name('support-tickets.index');

        Route::post('/support-tickets', [SupportTicketController::class, 'store'])
            ->name('support-tickets.store');

        Route::get('/support-tickets/{ticketId}', [SupportTicketController::class, 'show'])
            ->name('support-tickets.show');

        Route::post('/support-tickets/{ticketId}/replies', [SupportTicketController::class, 'storeReply'])
            ->name('support-tickets.replies.store');

        Route::get('/support-ticket-attachments/{attachmentId}/download', [SupportTicketController::class, 'downloadAttachment'])
            ->name('support-ticket-attachments.download');
    });
