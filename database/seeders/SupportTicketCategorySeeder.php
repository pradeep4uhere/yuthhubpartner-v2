<?php

namespace Database\Seeders;

use App\Models\SupportTicketCategory;
use Illuminate\Database\Seeder;

class SupportTicketCategorySeeder extends Seeder
{
    /**
     * Insert or update the default support ticket categories.
     *
     * Existing categories are updated by name without deleting tickets
     * or automatically reactivating categories disabled by an admin.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Login & Account Access',
                'description' => 'Login, password, and account access issues.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Learner Management',
                'description' => 'Learner registration, profile, and learner data issues.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Event & Activity Submission',
                'description' => 'Event transactions, activity submissions, and review issues.',
                'sort_order' => 3,
            ],
            [
                'name' => 'Placement & Job Updates',
                'description' => 'Placement records, job activities, and employment updates.',
                'sort_order' => 4,
            ],
            [
                'name' => 'Social Protection',
                'description' => 'Social protection activity and submission issues.',
                'sort_order' => 5,
            ],
            [
                'name' => 'Reports & Downloads',
                'description' => 'Reports, exports, and download issues.',
                'sort_order' => 6,
            ],
            [
                'name' => 'Technical Issue',
                'description' => 'Application errors, bugs, and unexpected behaviour.',
                'sort_order' => 7,
            ],
            [
                'name' => 'Other',
                'description' => 'Support requests that do not fit another category.',
                'sort_order' => 8,
            ],
        ];

        foreach ($categories as $category) {
            SupportTicketCategory::query()->updateOrCreate(
                ['name' => $category['name']],
                [
                    'description' => $category['description'],
                    'sort_order' => $category['sort_order'],
                ]
            );
        }
    }
}
