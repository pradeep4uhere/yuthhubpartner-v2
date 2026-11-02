<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminRolePermissionController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\FieldAgentController;
use App\Http\Controllers\PartnerCenterController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\PartnerPlacementUserController;
use App\Http\Controllers\LearnerController;
use App\Http\Controllers\EventMasterController;
use App\Http\Controllers\EventCategoryMasterController;
use App\Http\Controllers\EventMasterTypeController;

use App\Http\Controllers\Admin\EventTransactionController;
use App\Http\Controllers\Admin\DownloadController;
use App\Http\Controllers\Admin\LogHistoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LearnerSyncController;

Route::prefix('admin')->name('admin.')->group(function (): void {

    /*
    |--------------------------------------------------------------------------
    | Learner Synchronization
    |--------------------------------------------------------------------------
    */
  Route::middleware('auth:admin')->group(function (): void {
    Route::prefix('learner-sync')
        ->name('learner-sync.')
        ->middleware('can:learners.sync')
        ->controller(LearnerSyncController::class)
        ->group(function (): void {

            Route::get('/', 'index')->name('index');

            Route::get('/data', 'data')->name('data');

            Route::post('/run', 'run')->name('run');

            Route::get('/{syncLog}/status', 'status')->name('status');

            Route::get('/{syncLog}/pages', 'pages')->name('pages');

            Route::get('/{syncLog}/records', 'records')->name('records');
        });

     });

    // Admin login.
    Route::get('/login', [AdminAuthController::class, 'create'])
        ->name('login');

    // Authenticate Admin.
    Route::post('/login', [AdminAuthController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');

    Route::middleware('auth:admin')->group(function (): void {

        // Admin logout.
        Route::post('/logout', [AdminAuthController::class, 'destroy'])
            ->name('logout');

        // Admin dashboard.
        Route::get('/', AdminController::class)
            ->middleware('can:admin.access')
            ->name('dashboard');

        // Dashboard APIs.
        Route::get('/dashboard-data', [DashboardController::class, 'data'])
            ->name('admin.dashboard.data');

        Route::get('/dashboard/monthly-gender', [DashboardController::class, 'monthlyGender'])
            ->name('dashboard.monthly-gender');

        Route::get('/dashboard/event-transaction-status', [DashboardController::class, 'eventTransactionStatus'])
            ->name('dashboard.event-transaction-status');

        Route::get('/dashboard/learner-gender-status', [DashboardController::class, 'learnerGenderStatus'])
            ->name('dashboard.learner-gender-status');

        Route::get('/dashboard/learner-age-distribution', [DashboardController::class, 'learnerAgeDistribution'])
            ->name('dashboard.learner-age-distribution');

        Route::get('/dashboard/event-transaction-counts', [DashboardController::class, 'eventTransactionCounts'])
            ->name('dashboard.event-transaction-counts');

        Route::get('/dashboard/learners-by-programme', [DashboardController::class, 'learnersByProgramme'])
            ->name('dashboard.learners-by-programme');

        Route::get('/dashboard/top-10-field-agents', [DashboardController::class, 'top10FieldAgents'])
            ->name('dashboard.top-10-field-agents');

        Route::get('/dashboard/event-review-performance', [DashboardController::class, 'eventReviewPerformance'])
            ->name('dashboard.event-review-performance');


        /*
        |--------------------------------------------------------------------------
        | Admin Users
        |--------------------------------------------------------------------------
        */

        Route::get('/users', [AdminUserController::class, 'index'])
            ->middleware('can:admins.manage')->name('users.index');

        Route::post('/users', [AdminUserController::class, 'store'])
            ->middleware('can:admins.manage')->name('users.store');

        Route::patch('/users/{admin}/status', [AdminUserController::class, 'updateStatus'])
            ->middleware('can:admins.manage')->name('users.status');

        Route::get('/users/permissions', [AdminRolePermissionController::class, 'edit'])
            ->middleware('can:admins.manage')->name('users.permissions.edit');

        Route::put('/users/permissions', [AdminRolePermissionController::class, 'update'])
            ->middleware('can:admins.manage')->name('users.permissions.update');


        /*
        |--------------------------------------------------------------------------
        | Partners
        |--------------------------------------------------------------------------
        */

        Route::get('/partners', [PartnerController::class, 'index'])
            ->middleware('can:partners.view')->name('partners.index');

        Route::get('/partners/create', [PartnerController::class, 'create'])
            ->middleware('can:partners.create')->name('partners.create');

        Route::post('/partners', [PartnerController::class, 'store'])
            ->middleware('can:partners.create')->name('partners.store');

        Route::get('/partners/{partner}/edit', [PartnerController::class, 'edit'])
            ->middleware('can:partners.update')->name('partners.edit');

        Route::put('/partners/{partner}', [PartnerController::class, 'update'])
            ->middleware('can:partners.update')->name('partners.update');

        Route::delete('/partners/{partner}', [PartnerController::class, 'destroy'])
            ->middleware('can:partners.delete')->name('partners.destroy');


        /*
        |--------------------------------------------------------------------------
        | Partner Centers
        |--------------------------------------------------------------------------
        */

        Route::get('/partner-centers', [PartnerCenterController::class, 'index'])
            ->middleware('can:partner_centers.view')->name('partner-centers.index');

        Route::get('/partner-centers/create', [PartnerCenterController::class, 'create'])
            ->middleware('can:partner_centers.create')->name('partner-centers.create');

        Route::post('/partner-centers', [PartnerCenterController::class, 'store'])
            ->middleware('can:partner_centers.create')->name('partner-centers.store');

        Route::get('/partner-centers/{partnerCenter}/edit', [PartnerCenterController::class, 'edit'])
            ->middleware('can:partner_centers.update')->name('partner-centers.edit');

        Route::put('/partner-centers/{partnerCenter}', [PartnerCenterController::class, 'update'])
            ->middleware('can:partner_centers.update')->name('partner-centers.update');

        Route::delete('/partner-centers/{partnerCenter}', [PartnerCenterController::class, 'destroy'])
            ->middleware('can:partner_centers.delete')->name('partner-centers.destroy');


        /*
        |--------------------------------------------------------------------------
        | Placement Partner Users
        |--------------------------------------------------------------------------
        */

        Route::get('/partner-placement-users', [PartnerPlacementUserController::class, 'index'])
            ->middleware('can:partner_placement_users.view')->name('partner-placement-users.index');

        Route::get('/partner-placement-users/create', [PartnerPlacementUserController::class, 'create'])
            ->middleware('can:partner_placement_users.create')->name('partner-placement-users.create');

        Route::post('/partner-placement-users', [PartnerPlacementUserController::class, 'store'])
            ->middleware('can:partner_placement_users.create')->name('partner-placement-users.store');

        Route::get('/partner-placement-users/{partnerPlacementUser}/edit', [PartnerPlacementUserController::class, 'edit'])
            ->middleware('can:partner_placement_users.update')->name('partner-placement-users.edit');

        Route::put('/partner-placement-users/{partnerPlacementUser}', [PartnerPlacementUserController::class, 'update'])
            ->middleware('can:partner_placement_users.update')->name('partner-placement-users.update');

        Route::delete('/partner-placement-users/{partnerPlacementUser}', [PartnerPlacementUserController::class, 'destroy'])
            ->middleware('can:partner_placement_users.delete')->name('partner-placement-users.destroy');


        /*
        |--------------------------------------------------------------------------
        | Field Agents
        |--------------------------------------------------------------------------
        */

        Route::get('/field-agents', [FieldAgentController::class, 'index'])
            ->middleware('can:field_agents.view')->name('field-agents.index');

        Route::get('/field-agents/create', [FieldAgentController::class, 'create'])
            ->middleware('can:field_agents.create')->name('field-agents.create');

        Route::post('/field-agents', [FieldAgentController::class, 'store'])
            ->middleware('can:field_agents.create')->name('field-agents.store');

        Route::get('/field-agents/{fieldAgent}/edit', [FieldAgentController::class, 'edit'])
            ->middleware('can:field_agents.update')->name('field-agents.edit');

        Route::put('/field-agents/{fieldAgent}', [FieldAgentController::class, 'update'])
            ->middleware('can:field_agents.update')->name('field-agents.update');

        Route::delete('/field-agents/{fieldAgent}', [FieldAgentController::class, 'destroy'])
            ->middleware('can:field_agents.delete')->name('field-agents.destroy');

        Route::post('/field-agents/export', [FieldAgentController::class, 'export'])
            ->middleware('can:field_agents.export')->name('field-agents.export');


        /*
        |--------------------------------------------------------------------------
        | Learners
        |--------------------------------------------------------------------------
        */

        Route::get('/learners', [LearnerController::class, 'index'])
            ->middleware('can:learners.view')->name('learners.index');

        Route::get('/learners/create', [LearnerController::class, 'create'])
            ->middleware('can:learners.create')->name('learners.create');

        Route::post('/learners', [LearnerController::class, 'store'])
            ->middleware('can:learners.create')->name('learners.store');

        Route::get('/learners/{learner}/edit', [LearnerController::class, 'edit'])
            ->middleware('can:learners.update')->name('learners.edit');

        Route::put('/learners/{learner}', [LearnerController::class, 'update'])
            ->middleware('can:learners.update')->name('learners.update');

        Route::delete('/learners/{learner}', [LearnerController::class, 'destroy'])
            ->middleware('can:learners.delete')->name('learners.destroy');

        Route::post('/learners/export', [LearnerController::class, 'export'])
            ->name('learners.export');

        Route::get('/learners/export/download/{export}', [LearnerController::class, 'downloadExport'])
            ->name('learners.export.download');

        Route::get('/learners/export/{export}/status', [LearnerController::class, 'exportStatus'])
            ->name('learners.export.status');


        /*
        |--------------------------------------------------------------------------
        | Event Masters
        |--------------------------------------------------------------------------
        */

        Route::resource('event-masters', EventMasterController::class)
            ->except(['show']);

        Route::get('/event-masters/categories/{eventMasterType}', [EventMasterController::class, 'categories'])
            ->name('event-masters.categories');

        Route::get('/event-categories/by-type/{typeId}', [EventMasterController::class, 'byType'])
            ->name('event-categories.by-type');


        /*
        |--------------------------------------------------------------------------
        | Event Categories
        |--------------------------------------------------------------------------
        */

        Route::resource('event-categories', EventCategoryMasterController::class)
            ->except(['show']);


        /*
        |--------------------------------------------------------------------------
        | Event Types
        |--------------------------------------------------------------------------
        */

        Route::resource('event-types', EventMasterTypeController::class)
            ->except(['show']);


        /*
        |--------------------------------------------------------------------------
        | Event Transactions
        |--------------------------------------------------------------------------
        */

        Route::resource('event-transactions', EventTransactionController::class)
            ->except(['create', 'store']);

        Route::post('/event-transactions/{eventTransaction}/under-review', [EventTransactionController::class, 'underReview'])
            ->name('event-transactions.under-review');

        Route::post('/event-transactions/{eventTransaction}/approve', [EventTransactionController::class, 'approve'])
            ->name('event-transactions.approve');

        Route::post('/event-transactions/{eventTransaction}/reject', [EventTransactionController::class, 'reject'])
            ->name('event-transactions.reject');


        /*
        |--------------------------------------------------------------------------
        | Downloads
        |--------------------------------------------------------------------------
        */

        Route::get('/downloads', [DownloadController::class, 'index'])
            ->name('downloads.index');

        Route::get('/downloads/{export}/download', [DownloadController::class, 'download'])
            ->name('downloads.download');

        Route::get('/downloads/{export}/status', [DownloadController::class, 'status'])
            ->name('downloads.status');

        Route::post('/downloads/{export}/process', [DownloadController::class, 'process'])
            ->name('downloads.process');


        /*
        |--------------------------------------------------------------------------
        | Log History
        |--------------------------------------------------------------------------
        */

        Route::get('/log-history', [LogHistoryController::class, 'index'])
            ->name('logs.index');

        Route::post('/logs/clear', [LogHistoryController::class, 'clear'])
            ->name('logs.clear');
    });
});
