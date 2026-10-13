<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IndustryTypeSeeder extends Seeder
{
    public function run(): void
    {
        $industryTypes = [
            'Information Technology (IT)',
            'ITES',
            'Banking, Financial Services & Insurance (BFSI)',
            'Healthcare & Pharmaceuticals',
            'Retail & E-commerce',
            'Hospitality & Tourism',
            'Manufacturing & Engineering',
            'Telecom',
            'Construction & Infrastructure',
            'Education & EdTech',
            'Logistics & Transportation',
        ];

        foreach ($industryTypes as $industryType) {
            DB::table('industry_types')->updateOrInsert(
                ['name' => $industryType],
                [
                    'status' => 1,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
