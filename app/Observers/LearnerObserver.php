<?php

namespace App\Observers;

use App\Models\Learner;
use App\Services\LearnerCourseImportMatcher;

class LearnerObserver
{
    /**
     * Match pending course-import records when a learner is created.
     */
    public function created(Learner $learner): void
    {
        app(LearnerCourseImportMatcher::class)
            ->matchLearner($learner);
    }

    /**
     * Recheck pending course-import records when a learner's phone number changes.
     */
    public function updated(Learner $learner): void
    {
        if (!$learner->wasChanged('phone_number')) {
            return;
        }

        app(LearnerCourseImportMatcher::class)
            ->matchLearner($learner);
    }
}
