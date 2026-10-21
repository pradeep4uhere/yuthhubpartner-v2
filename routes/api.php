<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EventTransactionController;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use App\Http\Controllers\Api\FieldAgent\EventActivityController;
use App\Http\Controllers\Api\FieldAgent\DashboardStatsController;
use App\Http\Controllers\Api\FieldAgent\DashboardChartsController;
use App\Http\Controllers\Api\FieldAgent\LearnerController;
use App\Http\Controllers\Api\FieldAgent\ChangePasswordController;
use App\Http\Controllers\Api\FieldAgent\MpinSettingsController;
use App\Http\Controllers\Api\SupportTicketController;
use App\Http\Controllers\Api\Partner\PartnerDashboardApiController;

Route::middleware(['web', 'auth:partner'])
    ->prefix('partner')
    ->name('api.partner.')
    ->group(function () {
        Route::post('/support-tickets', [SupportTicketController::class, 'store'])
            ->name('support-tickets.store');
        Route::get('/dashboard/summary',[PartnerDashboardApiController::class, 'summary'])->name('dashboard.summary');
        Route::get('/dashboard/growth-overview',[PartnerDashboardApiController::class, 'growthOverview'])->name('dashboard.growth');
        // Get transaction distribution grouped by event transaction type.
        Route::get('/dashboard/event-transactions-by-type',[PartnerDashboardApiController::class, 'eventTransactionsByType'])->name('dashboard.event-transactions-by-type');
        // Returns the latest five activities for the authenticated partner.
        Route::get('/dashboard/recent-activities',[PartnerDashboardApiController::class, 'recentActivities']
        )->name('dashboard.recent-activities');

        Route::get('/dashboard/top-10-field-agents',[PartnerDashboardApiController::class, 'top10FieldAgents'])->name('dashboard.top-10-field-agents');

        Route::get('/dashboard/learner-age-distribution',[PartnerDashboardApiController::class, 'learnerAgeDistribution'])->name('dashboard.learner-age-distribution');
        Route::get('/dashboard/event-review-performance',[PartnerDashboardApiController::class, 'eventReviewPerformance'])->name('dashboard.event-transaction-status');
        Route::get('/learners', [PartnerDashboardApiController::class,'indexLearners'])->name('learners.index');

        Route::get('/dashboard/partner-center-earnings',[PartnerDashboardApiController::class, 'partnerCenterEarnings']);

        Route::get('/dashboard/learner-registration-growth',[PartnerDashboardApiController::class, 'learnerRegistrationGrowth']);



    });

Route::middleware(['web', 'auth:partner_center'])
    ->prefix('partner-center')
    ->name('api.partner-center.')
    ->group(function () {
        Route::post('/support-tickets', [SupportTicketController::class, 'store'])
            ->name('support-tickets.store');
    });

Route::middleware(['web', 'auth:partner_placement'])
    ->prefix('partner-placement')
    ->name('api.partner-placement.')
    ->group(function () {
        Route::post('/support-tickets', [SupportTicketController::class, 'store'])
            ->name('support-tickets.store');
    });

Route::middleware(['web', 'auth:field_agent'])
    ->prefix('field-agent')
    ->name('api.field-agent.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Allows authenticated Field Agents to create tickets, view ticket lists,
        | access ticket details, submit replies, view categories, and download
        | ticket attachments.
        */
        Route::post('/support-tickets', [SupportTicketController::class, 'store'])
            ->name('support-tickets.store');

        Route::get('/support-tickets', [SupportTicketController::class, 'index'])
            ->name('support-tickets.index');

        Route::get('/support-tickets/{ticketId}', [SupportTicketController::class, 'show'])
            ->whereNumber('ticketId')
            ->name('support-tickets.show');

        Route::post('/support-tickets/{ticketId}/replies', [SupportTicketController::class, 'storeReply'])
            ->whereNumber('ticketId')
            ->name('support-tickets.replies.store');

        Route::get('/support-ticket-categories', [SupportTicketController::class, 'categories'])
            ->name('support-tickets.categories');

        Route::get('/support-ticket-attachments/{attachmentId}/download', [SupportTicketController::class, 'downloadAttachment'])
            ->whereNumber('attachmentId')
            ->name('support-ticket-attachments.download');



        Route::get('/event-activities',[EventActivityController::class, 'index'])->name('event-activities.index');
        Route::get('/event-activities/{id}',[EventActivityController::class, 'show'])->whereNumber('id')->name('event-activities.show');
        Route::put('/event-activities/{id}', [EventActivityController::class,'update',])->whereNumber('id')->name('event-activities.update');
        /**
         * Store a new event activity for the authenticated Field Agent.
         *
         * @return void
         */
        Route::post('/event-activities',[EventActivityController::class, 'store'])->name('api.field-agent.event-activities.store');

        /*
        |--------------------------------------------------------------------------
        | Export Field Agent Event Activities
        |--------------------------------------------------------------------------
        | Exports all matching activities as an Excel workbook.
        | Keep this route before the dynamic /{id} routes.
        |--------------------------------------------------------------------------
        */
        Route::get('/event-activities/export',[EventActivityController::class, 'export']);

        Route::get('/event-activity-options',[EventActivityController::class, 'options'])->name('event-activities.options');
        Route::get('/event-activity-categories/{typeId}',[EventActivityController::class, 'categories'])->whereNumber('typeId')->name('event-activities.categories');
        Route::get('/event-activity-types',[EventActivityController::class, 'eventTypes']);
        Route::get('/event-activity-types/{typeId}/categories',[EventActivityController::class, 'eventCategories'])->whereNumber('typeId');
        Route::get('/learners/search', [EventActivityController::class,'searchLearners'])->name('learners.search');

        Route::get('/learners',[LearnerController::class, 'index'])->name('api.field-agent.learners.index');

        Route::get('/learners/activity-counts',[LearnerController::class, 'activityCounts'])->name('field-agent.learners.activity-counts');
        /*
        |--------------------------------------------------------------------------
        | Change Field Agent Password
        |--------------------------------------------------------------------------
        | Allows only the authenticated Field Agent to update their password.
        */
        Route::post('/change-password',[ChangePasswordController::class, 'update'])->name('change-password');

        Route::get('/dashboard-stats',[DashboardStatsController::class, 'index'])->name('dashboard-stats');
        Route::get('/dashboard-charts', [DashboardChartsController::class, 'index'])->name('dashboard-charts');


        /*
        |--------------------------------------------------------------------------
        | Field Agent MPIN Settings
        |--------------------------------------------------------------------------
        | Read and update MPIN settings for the authenticated Field Agent.
        | MPIN login remains disabled until a valid MPIN has been configured.
        */
        Route::get('/mpin/settings',[MpinSettingsController::class, 'show'])->name('mpin.settings.show');
        Route::put('/mpin/settings',[MpinSettingsController::class, 'update'])->name('mpin.settings.update');

    });


Route::middleware([
      EncryptCookies::class,
      AddQueuedCookiesToResponse::class,
      StartSession::class,
      'event.transaction.auth'
    ])
    ->prefix('event-transactions')
    ->group(function () {

        Route::get('/', [EventTransactionController::class, 'index'])
            ->name('api.event-transactions.index');

        Route::post('/', [EventTransactionController::class, 'store'])
            ->name('api.event-transactions.store');

        // IMPORTANT: static route before /{eventTransaction}
        Route::get('/auth-test', [
            EventTransactionController::class,
            'authTest'
        ])->name('api.event-transactions.auth-test');

        Route::get('/{eventTransaction}', [
            EventTransactionController::class,
            'show'
        ])->name('api.event-transactions.show');

        Route::put('/{eventTransaction}', [
            EventTransactionController::class,
            'update'
        ])->name('api.event-transactions.update');

        Route::delete('/{eventTransaction}', [
            EventTransactionController::class,
            'destroy'
        ])->name('api.event-transactions.destroy');

        Route::post('/{eventTransaction}/submit', [
            EventTransactionController::class,
            'submit'
        ])->name('api.event-transactions.submit');

        Route::post('/{eventTransaction}/under-review', [
            EventTransactionController::class,
            'underReview'
        ])->name('api.event-transactions.under-review');

        Route::post('/{eventTransaction}/approve', [
            EventTransactionController::class,
            'approve'
        ])->name('api.event-transactions.approve');

        Route::post('/{eventTransaction}/reject', [
            EventTransactionController::class,
            'reject'
        ])->name('api.event-transactions.reject');

        Route::post('/{eventTransaction}/resubmit', [
            EventTransactionController::class,
            'resubmit'
        ])->name('api.event-transactions.resubmit');
    });
