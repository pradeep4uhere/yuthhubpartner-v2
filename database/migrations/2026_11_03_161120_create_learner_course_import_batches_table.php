<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the learner course import batch tracking table.
     */
    public function up(): void
    {
        Schema::create('learner_course_import_batches', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch_id')->unique();
            $table->string('original_filename');
            $table->string('file_path')->nullable();

            $table->enum('status', [
                'queued',
                'processing',
                'completed',
                'failed',
            ])->default('queued')->index();

            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('matched_rows')->default(0);
            $table->unsignedInteger('pending_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);

            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Remove the learner course import batch tracking table.
     */
    public function down(): void
    {
        Schema::dropIfExists('learner_course_import_batches');
    }
};
