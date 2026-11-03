<?php

namespace App\Providers;

use App\Models\EventTransaction;
use App\Models\Learner;
use App\Observers\LearnerObserver;
use App\Policies\EventTransactionPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap application policies and model observers.
     */
    public function boot(): void
    {
        // Register the EventTransaction authorization policy.
        Gate::policy(
            EventTransaction::class,
            EventTransactionPolicy::class
        );

        // Register automatic learner course-import matching.
        Learner::observe(LearnerObserver::class);
    }
}
