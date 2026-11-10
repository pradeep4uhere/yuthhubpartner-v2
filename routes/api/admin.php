<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\LearnerApiController;
use App\Http\Controllers\Api\Admin\AdminSupportController;
use App\Http\Controllers\Api\Admin\AdminAnnouncementController;

Route::prefix('admin')
    ->name('api.admin.')
    ->middleware(['web', 'auth:admin'])
    ->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Admin Support & Ticket Management
    |--------------------------------------------------------------------------
    */

    // Temporarily verify the authenticated Admin session.
    Route::get('/support/auth-check', function () {
        return response()->json([
            'authenticated' => auth('admin')->check(),
            'admin_id' => auth('admin')->id(),
            'session_id' => session()->getId(),
        ]);
    })->middleware('can:support-tickets.view');


    // Verify Admin authentication and view permission for ticket details.
    Route::get('/support/ticket-auth-check/{ticket}', function ($ticket) {
        return response()->json([
            'authenticated' => auth('admin')->check(),
            'admin_id' => auth('admin')->id(),
            'can_view_tickets' => auth('admin')->user()
                ? auth('admin')->user()->can('support-tickets.view')
                : false,
            'ticket_id' => $ticket,
        ]);
    })->middleware('can:support-tickets.view');

    Route::prefix('support')
        ->name('support.')
        ->group(function () {


        // Check Admin authentication and reply permission.
        Route::get('/reply-auth-check', function () {
            $admin = auth('admin')->user();

            return response()->json([
                'authenticated' => auth('admin')->check(),
                'admin_id' => auth('admin')->id(),
                'can_view' => $admin
                    ? $admin->can('support-tickets.view')
                    : false,
                'can_reply' => $admin
                    ? $admin->can('support-tickets.reply')
                    : false,
                'roles' => $admin
                    ? $admin->getRoleNames()
                    : [],
            ]);
        });
            // Dashboard statistics and ticket listing.
            Route::get('/summary', [AdminSupportController::class, 'summary'])
                ->middleware('can:support-tickets.view')
                ->name('summary');

            Route::get('/tickets', [AdminSupportController::class, 'index'])
                ->middleware('can:support-tickets.view')
                ->name('tickets.index');

            Route::get('/tickets/{ticket}', [AdminSupportController::class, 'show'])
                ->middleware('can:support-tickets.view')
                ->name('tickets.show');

            // Public replies and private internal notes.
            Route::post('/tickets/{ticket}/reply', [AdminSupportController::class, 'reply'])
                ->middleware('can:support-tickets.reply')
                ->name('tickets.reply');

            Route::post('/tickets/{ticket}/internal-note', [AdminSupportController::class, 'internalNote'])
                ->middleware('can:support-tickets.internal-notes')
                ->name('tickets.internal-note');

            // Ticket management.
            Route::patch('/tickets/{ticket}/status', [AdminSupportController::class, 'updateStatus'])
                ->middleware('can:support-tickets.manage')
                ->name('tickets.status');

            Route::patch('/tickets/{ticket}/priority', [AdminSupportController::class, 'updatePriority'])
                ->middleware('can:support-tickets.manage')
                ->name('tickets.priority');

            Route::patch('/tickets/{ticket}/assign', [AdminSupportController::class, 'assign'])
                ->middleware('can:support-tickets.assign')
                ->name('tickets.assign');

            // Category management.
            Route::get('/categories', [AdminSupportController::class, 'categories'])
                ->middleware('can:support-tickets.view')
                ->name('categories.index');

            Route::post('/categories', [AdminSupportController::class, 'storeCategory'])
                ->middleware('can:support-tickets.categories.manage')
                ->name('categories.store');

            Route::patch('/categories/{categoryId}', [AdminSupportController::class, 'updateCategory'])
                ->middleware('can:support-tickets.categories.manage')
                ->name('categories.update');

            // Private attachment downloads.
            Route::get('/attachments/{attachmentId}/download', [AdminSupportController::class, 'downloadAttachment'])
                ->middleware('can:support-tickets.view')
                ->name('attachments.download');

            // Return eligible Admin accounts for ticket assignment.
            Route::get('/assignees', [AdminSupportController::class, 'assignees'])
                ->middleware('can:support-tickets.assign')
                ->name('assignees');

        });

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



            /*
            |--------------------------------------------------------------------------
            | Admin Announcement Management
            |--------------------------------------------------------------------------
            */

            Route::prefix('announcements')
                ->name('announcements.')
                ->group(function () {

                    // Return announcement listing and statistics.
                    Route::get('/', [AdminAnnouncementController::class, 'index'])
                        ->middleware('can:announcements.view')
                        ->name('index');

                    // Return searchable recipient options.
                    Route::get('/recipients', [
                        AdminAnnouncementController::class,
                        'recipientOptions',
                    ])->middleware('can:announcements.create')
                        ->name('recipients');

                    // Create a draft or publish an announcement.
                    Route::post('/', [AdminAnnouncementController::class, 'store'])
                        ->middleware('can:announcements.create')
                        ->name('store');

                    // Return announcement details.
                    Route::get('/{id}', [AdminAnnouncementController::class, 'show'])
                        ->whereNumber('id')
                        ->middleware('can:announcements.view')
                        ->name('show');

                    // Update an unpublished draft.
                    Route::put('/{id}', [AdminAnnouncementController::class, 'update'])
                        ->whereNumber('id')
                        ->middleware('can:announcements.update')
                        ->name('update');

                    // Publish a saved draft.
                    Route::post('/{id}/publish', [
                        AdminAnnouncementController::class,
                        'publish',
                    ])->whereNumber('id')
                        ->middleware('can:announcements.publish')
                        ->name('publish');


                        // Disable an announcement.
                    Route::post('/{id}/disable', [
                        AdminAnnouncementController::class,
                        'disable',
                    ])
                        ->middleware('can:announcements.publish')
                        ->name('disable');

                    // Enable a disabled announcement.
                    Route::post('/{id}/enable', [
                        AdminAnnouncementController::class,
                        'enable',
                    ])
                        ->middleware('can:announcements.publish')
                        ->name('enable');

                    // Download a private announcement attachment.
                    Route::get('/{id}/attachment', [
                        AdminAnnouncementController::class,
                        'attachment',
                    ])->whereNumber('id')
                        ->middleware('can:announcements.view')
                        ->name('attachment');

                    // Soft-delete an announcement.
                    Route::delete('/{id}', [
                        AdminAnnouncementController::class,
                        'destroy',
                    ])->whereNumber('id')
                        ->middleware('can:announcements.delete')
                        ->name('destroy');
                });







    });
