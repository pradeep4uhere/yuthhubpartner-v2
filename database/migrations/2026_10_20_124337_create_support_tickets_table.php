<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Create the common support tickets table for Field Agent,
     * Partner, Partner Center and Placement Partner User dashboards.
     *
     * The requester is stored using a polymorphic relationship because
     * tickets can be raised by different authenticated user models.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();

            // Unique reference shown to the user and support team.
            $table->string('ticket_number', 30)->unique();

            // Supports multiple dashboard user models as requesters.
            $table->morphs('requester');

            // Category selected when creating the ticket.
            $table->foreignId('category_id')
                ->constrained('support_ticket_categories')
                ->restrictOnDelete();

            // Issue details.
            $table->string('subject', 200);
            $table->text('description');
            $table->string('affected_module', 100)->nullable();

            // Priority and workflow status.
            $table->string('priority', 20)->default('normal');
            $table->string('status', 30)->default('open');

            // Flexible assignment to an authorized support/admin user.
            $table->nullableMorphs('assigned_to');

            // Latest reply and resolution timestamps.
            $table->timestamp('last_reply_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();

            // Optimize requester-specific ticket listings.
            $table->index(
                ['requester_type', 'requester_id', 'status'],
                'support_tickets_requester_status_idx'
            );

            // Optimize support dashboard filters.
            $table->index(
                ['status', 'priority'],
                'support_tickets_status_priority_idx'
            );

            $table->index(
                ['category_id', 'created_at'],
                'support_tickets_category_created_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drop the support tickets table.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
