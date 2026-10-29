<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create announcement, recipient and read-tracking tables.
     */
    public function up(): void
    {
        Schema::create('partner_announcements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('partner_id')
                ->constrained('partners')
                ->restrictOnDelete();

            $table->string('title', 200);
            $table->longText('message');

            $table->enum('priority', [
                'normal',
                'important',
                'urgent',
            ])->default('normal');

            $table->enum('target_type', [
                'all',
                'selected',
            ])->default('all');

            $table->enum('status', [
                'draft',
                'published',
            ])->default('draft');

            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();

            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['partner_id', 'status']);
            $table->index(['partner_id', 'published_at']);
        });

        Schema::create('partner_announcement_recipients', function (Blueprint $table) {
            $table->id();

            $table->foreignId('announcement_id')
                ->constrained('partner_announcements')
                ->cascadeOnDelete();

            $table->foreignId('field_agent_id')
                ->constrained('field_agents')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(
                ['announcement_id', 'field_agent_id'],
                'announcement_agent_unique'
            );

            $table->index('field_agent_id');
        });

        Schema::create('partner_announcement_reads', function (Blueprint $table) {
            $table->id();

            $table->foreignId('recipient_id')
                ->constrained('partner_announcement_recipients')
                ->cascadeOnDelete();

            $table->timestamp('read_at');
            $table->timestamps();

            $table->unique('recipient_id');
        });
    }

    /**
     * Remove the announcement tables in reverse dependency order.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_announcement_reads');
        Schema::dropIfExists('partner_announcement_recipients');
        Schema::dropIfExists('partner_announcements');
    }
};
