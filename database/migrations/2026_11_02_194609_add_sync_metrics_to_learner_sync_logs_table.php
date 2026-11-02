<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add detailed counters and run identification to existing sync logs.
     */
    public function up(): void
    {
        Schema::table('learner_sync_logs', function (Blueprint $table) {
            $table->uuid('run_id')->nullable()->unique();
            $table->string('sync_type', 30)->default('automatic');
            $table->unsignedInteger('total_processed')->default(0);
            $table->unsignedInteger('total_success')->default(0);
            $table->unsignedInteger('total_unchanged')->default(0);
            $table->unsignedInteger('total_duplicates')->default(0);
            $table->unsignedInteger('total_pages')->default(0);
            $table->unsignedInteger('duration_seconds')->nullable();
        });
    }

    /**
     * Remove the additional sync metrics.
     */
    public function down(): void
    {
        Schema::table('learner_sync_logs', function (Blueprint $table) {
            $table->dropUnique(['run_id']);
            $table->dropColumn([
                'run_id',
                'sync_type',
                'total_processed',
                'total_success',
                'total_unchanged',
                'total_duplicates',
                'total_pages',
                'duration_seconds',
            ]);
        });
    }
};
