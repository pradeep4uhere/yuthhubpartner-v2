<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FieldTypeSeeder extends Seeder
{
    public function run(): void
    {
        $fieldTypes = [
            'Sales',
            'Customer Care',
            'Marketing',
            'Distribution / Logistics / Delivery',
            'Manufacturing Operations',
            'Service Operations',
            'HR',
            'Admin',
            'Finance & Accounts',
        ];

        foreach ($fieldTypes as $fieldType) {
            DB::table('field_types')->updateOrInsert(
                ['name' => $fieldType],
                [
                    'status' => 1,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
