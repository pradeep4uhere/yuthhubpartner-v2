<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the support ticket attachments table.
     *
     * Stores files uploaded with support tickets and ticket replies.
     * Each attachment belongs to a ticket and may optionally belong
     * to a specific reply.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('support_ticket_attachments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('support_ticket_id')
                ->constrained('support_tickets')
                ->cascadeOnDelete();

            $table->foreignId('support_ticket_reply_id')
                ->nullable()
                ->constrained('support_ticket_replies')
                ->cascadeOnDelete();

            $table->nullableMorphs('uploaded_by');

            $table->string('original_name');
            $table->string('file_path');
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);

            $table->timestamps();

            $table->index(
                ['support_ticket_id', 'support_ticket_reply_id'],
                'support_ticket_attachments_ticket_reply_idx'
            );
        });
    }

    /**
     * Drop the support ticket attachments table.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('support_ticket_attachments');
    }
};
