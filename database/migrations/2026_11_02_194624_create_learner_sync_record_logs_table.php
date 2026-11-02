<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create individual learner synchronization issue logs.
     */
    public function up(): void
    {
        Schema::create('learner_sync_record_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('learner_sync_log_id')
                ->constrained('learner_sync_logs')
                ->cascadeOnDelete();

            $table->unsignedInteger('page_number')->nullable();

            $table->string('record_type', 30)->index();
            $table->string('phone_number', 30)->nullable()->index();
            $table->string('normalized_mobile', 20)->nullable()->index();

            $table->json('matching_learner_ids')->nullable();
            $table->json('source_payload')->nullable();

            $table->text('reason')->nullable();

            $table->timestamps();

            $table->index(['learner_sync_log_id', 'record_type']);
        });
    }

    /**
     * Drop individual learner synchronization issue logs.
     */
    public function down(): void
    {
        Schema::dropIfExists('learner_sync_record_logs');
    }
};
