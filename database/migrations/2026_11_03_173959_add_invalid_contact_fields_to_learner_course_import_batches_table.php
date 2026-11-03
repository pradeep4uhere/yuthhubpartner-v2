<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('learner_course_import_batches', function (Blueprint $table) {
             $table->unsignedInteger('skipped_rows')->default(0)->after('pending_rows');
             $table->string('invalid_file_path')->nullable()->after('failed_rows');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learner_course_import_batches', function (Blueprint $table) {
             $table->dropColumn([
                'skipped_rows',
                'invalid_file_path',
            ]);
        });
    }
};
