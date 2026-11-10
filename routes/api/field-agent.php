<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\SupportTicketController;

use App\Http\Controllers\Api\FieldAgent\EventActivityController;
use App\Http\Controllers\Api\FieldAgent\DashboardStatsController;
use App\Http\Controllers\Api\FieldAgent\DashboardChartsController;
use App\Http\Controllers\Api\FieldAgent\LearnerController;
use App\Http\Controllers\Api\FieldAgent\ChangePasswordController;
use App\Http\Controllers\Api\FieldAgent\MpinSettingsController;
use App\Http\Controllers\Api\FieldAgent\AnnouncementController;
use App\Http\Controllers\Api\FieldAgent\AdminAnnouncementController;

Route::middleware(['web', 'auth:field_agent'])
    ->prefix('field-agent')
    ->name('api.field-agent.')
    ->group(function (): void {


        /*
        |--------------------------------------------------------------------------
        | Announcements
        |--------------------------------------------------------------------------
        */

        Route::prefix('announcements')->name('announcements.')->group(function (): void {

            // List assigned announcements.
            Route::get('/', [AnnouncementController::class, 'index'])
                ->name('index');

            // Get unread announcement count.
            Route::get('/unread-count', [AnnouncementController::class, 'unreadCount'])
                ->name('unread-count');

            // Download announcement attachment.
            Route::get('/{id}/attachment', [AnnouncementController::class, 'attachment'])
                ->whereNumber('id')->name('attachment');

            // Show announcement details.
            Route::get('/{id}', [AnnouncementController::class, 'show'])
                ->whereNumber('id')->name('show');

            // Mark announcement as read.
            Route::post('/{id}/read', [AnnouncementController::class, 'markAsRead'])
                ->whereNumber('id')->name('read');
        });


        /*
        |--------------------------------------------------------------------------
        | Help & Support
        |--------------------------------------------------------------------------
        */

        Route::post('/support-tickets', [SupportTicketController::class, 'store'])
            ->name('support-tickets.store');

        Route::get('/support-tickets', [SupportTicketController::class, 'index'])
            ->name('support-tickets.index');

        Route::get('/support-tickets/{ticketId}', [SupportTicketController::class, 'show'])
            ->whereNumber('ticketId')->name('support-tickets.show');

        Route::post('/support-tickets/{ticketId}/replies', [SupportTicketController::class, 'storeReply'])
            ->whereNumber('ticketId')->name('support-tickets.replies.store');

        Route::get('/support-ticket-categories', [SupportTicketController::class, 'categories'])
            ->name('support-tickets.categories');

        Route::get('/support-ticket-attachments/{attachmentId}/download', [SupportTicketController::class, 'downloadAttachment'])
            ->whereNumber('attachmentId')->name('support-ticket-attachments.download');




        /*
        |--------------------------------------------------------------------------
        | Admin Announcements
        |--------------------------------------------------------------------------
        */

        Route::prefix('admin-announcements')
            ->name('admin-announcements.')
            ->group(function (): void {

                // List Admin announcements assigned to the authenticated agent.
                Route::get('/', [AdminAnnouncementController::class, 'index'])
                    ->name('index');

                // Get unread Admin announcement count.
                Route::get('/unread-count', [AdminAnnouncementController::class, 'unreadCount'])
                    ->name('unread-count');

                // Download or preview an assigned Admin announcement attachment.
                Route::get('/{id}/attachment', [AdminAnnouncementController::class, 'attachment'])
                    ->whereNumber('id')
                    ->name('attachment');

                // Show Admin announcement details.
                Route::get('/{id}', [AdminAnnouncementController::class, 'show'])
                    ->whereNumber('id')
                    ->name('show');

                // Mark an Admin announcement as read.
                Route::post('/{id}/read', [AdminAnnouncementController::class, 'markAsRead'])
                    ->whereNumber('id')
                    ->name('read');
            });


        /*
        |--------------------------------------------------------------------------
        | Event Activities
        |--------------------------------------------------------------------------
        */

        // Export all matching event activities.
        // Keep this static route before the dynamic ID route.
        Route::get('/event-activities/export', [EventActivityController::class, 'export'])
            ->name('event-activities.export');

        // List event activities.
        Route::get('/event-activities', [EventActivityController::class, 'index'])
            ->name('event-activities.index');

        // Create event activity.
        Route::post('/event-activities', [EventActivityController::class, 'store'])
            ->name('event-activities.store');

        // Show event activity.
        Route::get('/event-activities/{id}', [EventActivityController::class, 'show'])
            ->whereNumber('id')->name('event-activities.show');

        // Update event activity.
        Route::put('/event-activities/{id}', [EventActivityController::class, 'update'])
            ->whereNumber('id')->name('event-activities.update');


        /*
        |--------------------------------------------------------------------------
        | Event Activity Options
        |--------------------------------------------------------------------------
        */

        Route::get('/event-activity-options', [EventActivityController::class, 'options'])
            ->name('event-activities.options');

        Route::get('/event-activity-categories/{typeId}', [EventActivityController::class, 'categories'])
            ->whereNumber('typeId')->name('event-activities.categories');

        Route::get('/event-activity-types', [EventActivityController::class, 'eventTypes'])
            ->name('event-activities.types');

        Route::get('/event-activity-types/{typeId}/categories', [EventActivityController::class, 'eventCategories'])
            ->whereNumber('typeId')->name('event-activities.type-categories');


        /*
        |--------------------------------------------------------------------------
        | Learners
        |--------------------------------------------------------------------------
        */

        Route::get('/learners/search', [EventActivityController::class, 'searchLearners'])
            ->name('learners.search');

        Route::get('/learners', [LearnerController::class, 'index'])
            ->name('learners.index');

        Route::get('/learners/activity-counts', [LearnerController::class, 'activityCounts'])
            ->name('learners.activity-counts');


        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */

        Route::get('/dashboard-stats', [DashboardStatsController::class, 'index'])
            ->name('dashboard-stats');

        Route::get('/dashboard-charts', [DashboardChartsController::class, 'index'])
            ->name('dashboard-charts');


        /*
        |--------------------------------------------------------------------------
        | Change Password
        |--------------------------------------------------------------------------
        */

        Route::post('/change-password', [ChangePasswordController::class, 'update'])
            ->name('change-password');


        /*
        |--------------------------------------------------------------------------
        | MPIN Settings
        |--------------------------------------------------------------------------
        */

        Route::get('/mpin/settings', [MpinSettingsController::class, 'show'])
            ->name('mpin.settings.show');

        Route::put('/mpin/settings', [MpinSettingsController::class, 'update'])
            ->name('mpin.settings.update');
    });
