<?php

namespace App\Services;

use App\Models\Learner;
use App\Models\LearnerCourseImport;
use Illuminate\Support\Facades\DB;

class LearnerCourseImportMatcher
{
    /**
     * Match pending course-import records with a learner's phone number.
     */
    public function matchLearner(Learner $learner): void
    {
        $contact = trim((string) $learner->phone_number);

        if ($contact === '') {
            return;
        }

        DB::transaction(function () use ($learner, $contact): void {
            // Check whether any pending record has exactly 100% course completion.
            $hasCompletedCourse = LearnerCourseImport::query()
                ->where('contact', $contact)
                ->where('match_status', 'pending')
                ->where('course_completion', 100)
                ->exists();

            // Match all pending import rows for this learner's contact.
            LearnerCourseImport::query()
                ->where('contact', $contact)
                ->where('match_status', 'pending')
                ->update([
                    'learner_id' => $learner->id,
                    'match_status' => 'matched',
                    'matched_at' => now(),
                    'updated_at' => now(),
                ]);

            // Mark the learner completed if at least one matching row is 100%.
            if ($hasCompletedCourse) {
                Learner::query()
                    ->whereKey($learner->id)
                    ->update(['course_completed' => 1]);
            }
        });
    }
}
