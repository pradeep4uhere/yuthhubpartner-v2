<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the learner course CSV import records table.
     */
    public function up(): void
    {
        Schema::create('learner_course_imports', function (Blueprint $table) {
            $table->id();

            // Identifies all records belonging to one CSV upload.
            $table->uuid('import_batch_id')->index();

            // Original CSV data.
            $table->string('course_name')->nullable();
            $table->string('contact', 20)->nullable()->index();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->text('my_groups')->nullable();
            $table->string('age_in_years')->nullable();
            $table->string('gender')->nullable();
            $table->string('state')->nullable();
            $table->string('data_sharing_consent')->nullable();
            $table->unsignedSmallInteger('course_completion')->nullable();

            // Learner matching information.
            $table->foreignId('learner_id')
                ->nullable()
                ->constrained('learners')
                ->nullOnDelete();

            $table->enum('match_status', ['pending', 'matched'])
                ->default('pending')
                ->index();

            $table->timestamp('matched_at')->nullable();

            $table->timestamps();

            // Optimizes future matching of pending CSV records.
            $table->index(
                ['contact', 'match_status'],
                'learner_course_import_contact_status_idx'
            );
        });
    }

    /**
     * Drop the learner course CSV import records table.
     */
    public function down(): void
    {
        Schema::dropIfExists('learner_course_imports');
    }
};
