<?php

use Illuminate\Support\Facades\Route;

use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Session\Middleware\StartSession;

use App\Http\Controllers\Api\EventTransactionController;

Route::middleware([
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    'event.transaction.auth',
])
    ->prefix('event-transactions')
    ->name('api.event-transactions.')
    ->group(function (): void {

        // List event transactions.
        Route::get('/', [EventTransactionController::class, 'index'])
            ->name('index');

        // Create event transaction.
        Route::post('/', [EventTransactionController::class, 'store'])
            ->name('store');

        // Test event transaction authentication.
        Route::get('/auth-test', [EventTransactionController::class, 'authTest'])
            ->name('auth-test');

        // Show event transaction.
        Route::get('/{eventTransaction}', [EventTransactionController::class, 'show'])
            ->name('show');

        // Update event transaction.
        Route::put('/{eventTransaction}', [EventTransactionController::class, 'update'])
            ->name('update');

        // Delete event transaction.
        Route::delete('/{eventTransaction}', [EventTransactionController::class, 'destroy'])
            ->name('destroy');

        // Submit event transaction.
        Route::post('/{eventTransaction}/submit', [EventTransactionController::class, 'submit'])
            ->name('submit');

        // Move event transaction under review.
        Route::post('/{eventTransaction}/under-review', [EventTransactionController::class, 'underReview'])
            ->name('under-review');

        // Approve event transaction.
        Route::post('/{eventTransaction}/approve', [EventTransactionController::class, 'approve'])
            ->name('approve');

        // Reject event transaction.
        Route::post('/{eventTransaction}/reject', [EventTransactionController::class, 'reject'])
            ->name('reject');

        // Resubmit event transaction.
        Route::post('/{eventTransaction}/resubmit', [EventTransactionController::class, 'resubmit'])
            ->name('resubmit');
    });
