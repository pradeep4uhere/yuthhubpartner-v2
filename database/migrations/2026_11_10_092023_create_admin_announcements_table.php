<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the centralized Admin announcements table.
     */
    public function up(): void
    {
        Schema::create('admin_announcements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('admin_id')
                ->constrained('admins')
                ->restrictOnDelete();

            $table->string('title', 200);
            $table->longText('message');

            $table->string('priority', 20)->default('normal');

            $table->string('audience_type', 30);
            $table->string('target_type', 20)->default('all');

            $table->string('status', 20)->default('draft');

            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();

            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(
                ['audience_type', 'status'],
                'admin_announcements_audience_status_idx'
            );

            $table->index('published_at');
            $table->index('expires_at');
        });
    }

    /**
     * Drop the centralized Admin announcements table.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_announcements');
    }
};
