
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Change the batch error message column to TEXT.
     */
    public function up(): void
    {
        Schema::table('learner_course_import_batches', function (Blueprint $table) {
            $table->text('error_message')->nullable()->change();
        });
    }

    /**
     * Restore the error message column to VARCHAR(255).
     */
    public function down(): void
    {
        Schema::table('learner_course_import_batches', function (Blueprint $table) {
            $table->string('error_message', 255)->nullable()->change();
        });
    }
};
