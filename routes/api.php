<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EventTransactionController;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;

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