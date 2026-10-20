<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminRolePermissionController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\FieldAgentController;
use App\Http\Controllers\FieldAgentPortalController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PartnerCenterController;
use App\Http\Controllers\PartnerCenterPortalController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\PartnerPlacementPortalController;
use App\Http\Controllers\PartnerPlacementUserController;
use App\Http\Controllers\PartnerPortalController;
use App\Http\Controllers\LearnerController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EventMasterController;
use App\Http\Controllers\EventCategoryMasterController;
use App\Http\Controllers\EventMasterTypeController;
use App\Http\Controllers\Admin\EventTransactionController;
use App\Http\Controllers\Admin\DownloadController;
use App\Http\Controllers\Admin\LogHistoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\Api\Partner\PartnerDashboardApiController;


Route::get('/', HomeController::class)->name('home');
Route::post('/language', [LanguageController::class, 'update'])->name('language.update');

Route::prefix('field-agent')->name('field-agent.')->group(function (): void {
    Route::get('/login', [FieldAgentPortalController::class, 'create'])
        ->middleware('guest:field_agent')
        ->name('login');

    Route::post('/login', [FieldAgentPortalController::class, 'store'])
        ->middleware(['guest:field_agent', 'throttle:5,1'])
        ->name('login.store');

    Route::middleware('auth:field_agent')->group(function (): void {
        // Dedicated second-step MPIN verification screen.
        Route::get('/mpin-verification', [
            FieldAgentPortalController::class,
            'showMpinVerification',
        ])->middleware('throttle:10,1')->name('mpin.verify');

        // Submit the MPIN after successful password authentication.
        Route::post('/mpin-verification', [
            FieldAgentPortalController::class,
            'verifyMpin',
        ])->middleware('throttle:5,1')->name('mpin.verify.submit');

        Route::get('/dashboard', [
            FieldAgentPortalController::class,
            'dashboard',
        ])->name('dashboard');

        Route::post('/logout', [
            FieldAgentPortalController::class,
            'destroy',
        ])->name('logout');
    });
});

Route::prefix('partner-placement')->name('partner-placement.')->group(function (): void {
    Route::get('/login', [PartnerPlacementPortalController::class, 'create'])->middleware('guest:partner_placement')->name('login');
    Route::post('/login', [PartnerPlacementPortalController::class, 'store'])->middleware(['guest:partner_placement', 'throttle:5,1'])->name('login.store');
    Route::middleware('auth:partner_placement')->group(function (): void {
        Route::get('/dashboard', [PartnerPlacementPortalController::class, 'dashboard'])->name('dashboard');
        Route::post('/logout', [PartnerPlacementPortalController::class, 'destroy'])->name('logout');
    });
});

Route::prefix('partner')->name('partner.')->group(function (): void {
    Route::get('/login', [PartnerPortalController::class, 'create'])->middleware('guest:partner')->name('login');
    Route::post('/login', [PartnerPortalController::class, 'store'])->middleware(['guest:partner', 'throttle:5,1'])->name('login.store');
    Route::middleware('auth:partner')->group(function (): void {
        Route::get('/dashboard', [PartnerPortalController::class, 'dashboard'])->name('dashboard');
        Route::post('/logout', [PartnerPortalController::class, 'destroy'])->name('logout');

    });
});

Route::prefix('partner-center')->name('partner-center.')->group(function (): void {
    Route::get('/login', [PartnerCenterPortalController::class, 'create'])->middleware('guest:partner_center')->name('login');
    Route::post('/login', [PartnerCenterPortalController::class, 'store'])->middleware(['guest:partner_center', 'throttle:5,1'])->name('login.store');
    Route::middleware('auth:partner_center')->group(function (): void {
        Route::get('/dashboard', [PartnerCenterPortalController::class, 'dashboard'])->name('dashboard');
        Route::post('/logout', [PartnerCenterPortalController::class, 'destroy'])->name('logout');
    });
});

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/login', [AdminAuthController::class, 'create'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'store'])->middleware('throttle:5,1')->name('login.store');

    Route::middleware('auth:admin')->group(function (): void {
        Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');
        Route::get('/', AdminController::class)->middleware('can:admin.access')->name('dashboard');
        Route::get('/dashboard-data', [DashboardController::class,'data'])->name('admin.dashboard.data');
        Route::get('/dashboard/monthly-gender',[DashboardController::class, 'monthlyGender'])->name('dashboard.monthly-gender');
        Route::get('/dashboard/event-transaction-status',[DashboardController::class, 'eventTransactionStatus'])->name('dashboard.event-transaction-status');
        Route::get('/dashboard/learner-gender-status',[DashboardController::class, 'learnerGenderStatus'])->name('dashboard.learner-gender-status');
        Route::get('/dashboard/learner-age-distribution',[DashboardController::class, 'learnerAgeDistribution'])->name('dashboard.learner-age-distribution');
        Route::get('/dashboard/event-transaction-counts',[DashboardController::class, 'eventTransactionCounts'])->name('dashboard.event-transaction-counts');
        Route::get('/dashboard/learners-by-programme',[DashboardController::class, 'learnersByProgramme'])->name('dashboard.learners-by-programme');
        Route::get('/dashboard/top-10-field-agents',[DashboardController::class, 'top10FieldAgents'])->name('dashboard.top-10-field-agents');
        Route::get('/dashboard/event-review-performance',[DashboardController::class, 'eventReviewPerformance'])->name('dashboard.event-review-performance');


        Route::get('/users', [AdminUserController::class, 'index'])->middleware('can:admins.manage')->name('users.index');
        Route::post('/users', [AdminUserController::class, 'store'])->middleware('can:admins.manage')->name('users.store');
        Route::patch('/users/{admin}/status', [AdminUserController::class, 'updateStatus'])->middleware('can:admins.manage')->name('users.status');
        Route::get('/users/permissions', [AdminRolePermissionController::class, 'edit'])->middleware('can:admins.manage')->name('users.permissions.edit');
        Route::put('/users/permissions', [AdminRolePermissionController::class, 'update'])->middleware('can:admins.manage')->name('users.permissions.update');

        Route::get('/partners', [PartnerController::class, 'index'])->middleware('can:partners.view')->name('partners.index');
        Route::get('/partners/create', [PartnerController::class, 'create'])->middleware('can:partners.create')->name('partners.create');
        Route::post('/partners', [PartnerController::class, 'store'])->middleware('can:partners.create')->name('partners.store');
        Route::get('/partners/{partner}/edit', [PartnerController::class, 'edit'])->middleware('can:partners.update')->name('partners.edit');
        Route::put('/partners/{partner}', [PartnerController::class, 'update'])->middleware('can:partners.update')->name('partners.update');
        Route::delete('/partners/{partner}', [PartnerController::class, 'destroy'])->middleware('can:partners.delete')->name('partners.destroy');

        Route::get('/partner-centers', [PartnerCenterController::class, 'index'])->middleware('can:partner_centers.view')->name('partner-centers.index');
        Route::get('/partner-centers/create', [PartnerCenterController::class, 'create'])->middleware('can:partner_centers.create')->name('partner-centers.create');
        Route::post('/partner-centers', [PartnerCenterController::class, 'store'])->middleware('can:partner_centers.create')->name('partner-centers.store');
        Route::get('/partner-centers/{partnerCenter}/edit', [PartnerCenterController::class, 'edit'])->middleware('can:partner_centers.update')->name('partner-centers.edit');
        Route::put('/partner-centers/{partnerCenter}', [PartnerCenterController::class, 'update'])->middleware('can:partner_centers.update')->name('partner-centers.update');
        Route::delete('/partner-centers/{partnerCenter}', [PartnerCenterController::class, 'destroy'])->middleware('can:partner_centers.delete')->name('partner-centers.destroy');

        Route::get('/partner-placement-users', [PartnerPlacementUserController::class, 'index'])->middleware('can:partner_placement_users.view')->name('partner-placement-users.index');
        Route::get('/partner-placement-users/create', [PartnerPlacementUserController::class, 'create'])->middleware('can:partner_placement_users.create')->name('partner-placement-users.create');
        Route::post('/partner-placement-users', [PartnerPlacementUserController::class, 'store'])->middleware('can:partner_placement_users.create')->name('partner-placement-users.store');
        Route::get('/partner-placement-users/{partnerPlacementUser}/edit', [PartnerPlacementUserController::class, 'edit'])->middleware('can:partner_placement_users.update')->name('partner-placement-users.edit');
        Route::put('/partner-placement-users/{partnerPlacementUser}', [PartnerPlacementUserController::class, 'update'])->middleware('can:partner_placement_users.update')->name('partner-placement-users.update');
        Route::delete('/partner-placement-users/{partnerPlacementUser}', [PartnerPlacementUserController::class, 'destroy'])->middleware('can:partner_placement_users.delete')->name('partner-placement-users.destroy');

        Route::get('/field-agents', [FieldAgentController::class, 'index'])->middleware('can:field_agents.view')->name('field-agents.index');
        Route::get('/field-agents/create', [FieldAgentController::class, 'create'])->middleware('can:field_agents.create')->name('field-agents.create');
        Route::post('/field-agents', [FieldAgentController::class, 'store'])->middleware('can:field_agents.create')->name('field-agents.store');
        Route::get('/field-agents/{fieldAgent}/edit', [FieldAgentController::class, 'edit'])->middleware('can:field_agents.update')->name('field-agents.edit');
        Route::put('/field-agents/{fieldAgent}', [FieldAgentController::class, 'update'])->middleware('can:field_agents.update')->name('field-agents.update');
        Route::delete('/field-agents/{fieldAgent}', [FieldAgentController::class, 'destroy'])->middleware('can:field_agents.delete')->name('field-agents.destroy');
        Route::post('field-agents/export',[FieldAgentController::class, 'export'])->name('field-agents.export')->middleware('can:field_agents.export');


        Route::get('/learners', [LearnerController::class, 'index'])->middleware('can:learners.view')->name('learners.index');
        Route::get('/learners/create', [LearnerController::class, 'create'])->middleware('can:learners.create')->name('learners.create');
        Route::post('/learners', [LearnerController::class, 'store'])->middleware('can:learners.create')->name('learners.store');
        Route::get('/learners/{learner}/edit', [LearnerController::class, 'edit'])->middleware('can:learners.update')->name('learners.edit');
        Route::put('/learners/{learner}', [LearnerController::class, 'update'])->middleware('can:learners.update')->name('learners.update');
        Route::delete('/learners/{learner}', [LearnerController::class, 'destroy'])->middleware('can:learners.delete')->name('learners.destroy');
        // Learner Export
        Route::post('/learners/export', [LearnerController::class, 'export'])->name('learners.export');
        Route::get('/learners/export/download/{export}',[LearnerController::class, 'downloadExport'])->name('learners.export.download');
        Route::get('/downloads/{export}/status', [DownloadController::class, 'status'])->name('downloads.status');
        Route::get('/learners/export/{export}/status', [LearnerController::class, 'exportStatus'])->name('learners.export.status');


        Route::get('/event-masters', [EventMasterController::class, 'index'])
            ->middleware('can:event_masters.view')
            ->name('event-masters.index');

        Route::get('/event-masters/create', [EventMasterController::class, 'create'])
            ->middleware('can:event_masters.create')
            ->name('event-masters.create');

        Route::post('/event-masters', [EventMasterController::class, 'store'])
            ->middleware('can:event_masters.create')
            ->name('event-masters.store');

        Route::get('/event-masters/{eventMaster}/edit', [EventMasterController::class, 'edit'])
            ->middleware('can:event_masters.update')
            ->name('event-masters.edit');

        Route::put('/event-masters/{eventMaster}', [EventMasterController::class, 'update'])
            ->middleware('can:event_masters.update')
            ->name('event-masters.update');

        Route::delete('/event-masters/{eventMaster}', [EventMasterController::class, 'destroy'])
            ->middleware('can:event_masters.delete')
            ->name('event-masters.destroy');

        Route::get('/event-masters/categories/{eventMasterType}',[EventMasterController::class, 'categories'])
            ->middleware('can:event_masters.view')
            ->name('event-masters.categories');

        Route::get('/event-categories/by-type/{typeId}',[EventMasterController::class, 'byType'])->name('event-categories.by-type');




        Route::get('/event-categories', [EventCategoryMasterController::class, 'index'])
            ->middleware('can:event_categories.view')
            ->name('event-categories.index');

        Route::get('/event-categories/create', [EventCategoryMasterController::class, 'create'])
            ->middleware('can:event_categories.create')
            ->name('event-categories.create');

        Route::post('/event-categories', [EventCategoryMasterController::class, 'store'])
            ->middleware('can:event_categories.create')
            ->name('event-categories.store');

        Route::get('/event-categories/{eventCategory}/edit', [EventCategoryMasterController::class, 'edit'])
            ->middleware('can:event_categories.update')
            ->name('event-categories.edit');

        Route::put('/event-categories/{eventCategory}', [EventCategoryMasterController::class, 'update'])
            ->middleware('can:event_categories.update')
            ->name('event-categories.update');

        Route::delete('/event-categories/{eventCategory}', [EventCategoryMasterController::class, 'destroy'])
            ->middleware('can:event_categories.delete')
            ->name('event-categories.destroy');


        /***All Routes For Event Type *******/
        Route::get('/event-types', [EventMasterTypeController::class, 'index'])
            ->middleware('can:event_types.view')
            ->name('event-types.index');

        Route::get('/event-types/create', [EventMasterTypeController::class, 'create'])
            ->middleware('can:event_types.create')
            ->name('event-types.create');

        Route::post('/event-types', [EventMasterTypeController::class, 'store'])
            ->middleware('can:event_types.create')
            ->name('event-types.store');

        Route::get('/event-types/{eventType}/edit', [EventMasterTypeController::class, 'edit'])
            ->middleware('can:event_types.update')
            ->name('event-types.edit');

        Route::put('/event-types/{eventType}', [EventMasterTypeController::class, 'update'])
            ->middleware('can:event_types.update')
            ->name('event-types.update');

        Route::delete('/event-types/{eventType}', [EventMasterTypeController::class, 'destroy'])
            ->middleware('can:event_types.delete')
            ->name('event-types.destroy');




        Route::resource('event-transactions', EventTransactionController::class)->except(['create', 'store']);
        Route::post('event-transactions/{eventTransaction}/under-review',[EventTransactionController::class, 'underReview'])->name('event-transactions.under-review');
        Route::post('event-transactions/{eventTransaction}/approve',[EventTransactionController::class, 'approve'])->name('event-transactions.approve');
        Route::post('event-transactions/{eventTransaction}/reject',[EventTransactionController::class, 'reject'])->name('event-transactions.reject');
        Route::put('event-transactions/{eventTransaction}',[EventTransactionController::class, 'update'])->name('event-transactions.update');


        Route::get('/downloads', [DownloadController::class, 'index'])->name('downloads.index');
        Route::get('/downloads/{export}/download', [DownloadController::class, 'download'])->name('downloads.download');
        Route::get('/downloads/{export}/status', [DownloadController::class, 'status'])->name('downloads.status');
        Route::get('/downloads/{export}/download', [DownloadController::class, 'download'])->name('downloads.download');
        Route::post('/downloads/{export}/process', [DownloadController::class, 'process'])->name('downloads.process');


        Route::get('/log-history', [LogHistoryController::class,'index'])->name('logs.index');

        Route::post('/logs/clear', [LogHistoryController::class,'clear'])->name('logs.clear');

    });

});
