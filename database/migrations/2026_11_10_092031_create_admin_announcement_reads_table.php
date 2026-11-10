<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create independent read tracking for each announcement recipient.
     */
    public function up(): void
    {
        Schema::create('admin_announcement_reads', function (Blueprint $table) {
            $table->id();

            $table->foreignId('recipient_id')
                ->constrained('admin_announcement_recipients')
                ->cascadeOnDelete();

            $table->timestamp('read_at');

            $table->timestamps();

            $table->unique('recipient_id');
        });
    }

    /**
     * Drop Admin announcement read tracking.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_announcement_reads');
    }
};
