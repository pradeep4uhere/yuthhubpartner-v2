<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create API page-wise synchronization logs.
     */
    public function up(): void
    {
        Schema::create('learner_sync_page_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('learner_sync_log_id')
                ->constrained('learner_sync_logs')
                ->cascadeOnDelete();

            $table->unsignedInteger('page_number');
            $table->unsignedInteger('records_fetched')->default(0);
            $table->unsignedInteger('total_records')->default(0);
            $table->unsignedInteger('records_remaining')->default(0);

            $table->string('status', 30)->default('success');
            $table->text('error_message')->nullable();
            $table->timestamp('fetched_at')->nullable();

            $table->timestamps();

            $table->index(['learner_sync_log_id', 'page_number']);
        });
    }

    /**
     * Drop API page-wise synchronization logs.
     */
    public function down(): void
    {
        Schema::dropIfExists('learner_sync_page_logs');
    }
};
