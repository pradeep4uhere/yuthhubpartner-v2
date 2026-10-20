<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the support ticket replies table.
     *
     * Each reply belongs to a support ticket and records the sender
     * using a polymorphic relationship. Internal notes are marked
     * separately so the application can restrict their visibility.
     */
    public function up(): void
    {
        Schema::create('support_ticket_replies', function (Blueprint $table) {
            $table->id();

            $table->foreignId('support_ticket_id')
                ->constrained('support_tickets')
                ->cascadeOnDelete();

            $table->morphs('sender');

            $table->longText('message');

            $table->boolean('is_internal_note')->default(false);

            $table->timestamps();

            $table->index(
                ['support_ticket_id', 'created_at'],
                'support_ticket_replies_ticket_created_idx'
            );
        });
    }

    /**
     * Drop the support ticket replies table.
     *
     * This reverses the table creation performed by the up method.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_ticket_replies');
    }
};
