<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\LearnerApiController;

Route::prefix('admin')
    ->name('api.admin.')
    ->middleware(['web', 'auth:admin'])
    ->group(function () {

        // Get learner KPI summary.
        Route::get('/learners/summary', [LearnerApiController::class, 'summary'])
            ->middleware('can:learners.view')
            ->name('learners.summary');

        // Get learner analytics.
        Route::get('/learners/analytics', [LearnerApiController::class, 'analytics'])
            ->middleware('can:learners.view')
            ->name('learners.analytics');

        // Get filtered and paginated learners.
        Route::get('/learners', [LearnerApiController::class, 'listing'])
            ->middleware('can:learners.view')
            ->name('learners.index');

        // Get learner filter dropdown options.
        Route::get('/learners/filter-options', [LearnerApiController::class, 'filterOptions'])
            ->middleware('can:learners.view')
            ->name('learners.filter-options');
    });
