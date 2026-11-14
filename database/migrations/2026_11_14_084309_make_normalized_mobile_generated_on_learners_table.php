
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Convert normalized_mobile into a stored generated column.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE learners
            MODIFY COLUMN normalized_mobile VARCHAR(10)
            GENERATED ALWAYS AS (RIGHT(phone_number, 10)) STORED
        ");
    }

    /**
     * Restore normalized_mobile as a regular nullable column.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE learners
            MODIFY COLUMN normalized_mobile VARCHAR(10) NULL
        ");
    }
};
