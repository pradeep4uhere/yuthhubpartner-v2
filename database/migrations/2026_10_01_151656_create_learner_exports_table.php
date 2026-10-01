<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learner_exports', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('admin_id');

            $table->string('file_name')->nullable();
            $table->string('file_path')->nullable();

            $table->enum('status', [
                'queued',
                'processing',
                'completed',
                'failed',
            ])->default('queued');

            $table->unsignedBigInteger('total_records')->default(0);

            $table->text('filters')->nullable();

            $table->text('error_message')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index(['admin_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learner_exports');
    }
};
