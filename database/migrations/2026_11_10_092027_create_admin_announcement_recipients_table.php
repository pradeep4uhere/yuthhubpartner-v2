<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create recipient assignments for Admin announcements.
     */
    public function up(): void
    {
        Schema::create('admin_announcement_recipients', function (Blueprint $table) {
            $table->id();

            $table->foreignId('announcement_id')
                ->constrained('admin_announcements')
                ->cascadeOnDelete();

            // Supports FieldAgent, Partner and PartnerPlacementUser.
            $table->string('recipient_type');
            $table->unsignedBigInteger('recipient_id');

            $table->timestamps();

            $table->unique(
                ['announcement_id', 'recipient_type', 'recipient_id'],
                'admin_announcement_recipient_unique'
            );

            $table->index(
                ['recipient_type', 'recipient_id'],
                'admin_announcement_recipient_lookup_idx'
            );
        });
    }

    /**
     * Drop Admin announcement recipient assignments.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_announcement_recipients');
    }
};
