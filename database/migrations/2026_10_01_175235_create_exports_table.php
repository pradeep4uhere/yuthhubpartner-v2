<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exports', function (Blueprint $table) {
            $table->id();

            /*
             * Who requested this export?
             *
             * Admin
             * Partner
             * PlacementPartnerUser
             * FieldAgent
             */
            $table->string('requested_by_type');
            $table->unsignedBigInteger('requested_by_id');

            /*
             * What is being exported?
             *
             * placement_partners
             * partners
             * partner_centers
             * field_agents
             * learners
             * events
             * transactions
             * reports
             */
            $table->string('type', 50);

            /*
             * What data scope should be used?
             *
             * system
             * placement_partner
             * partner
             * field_agent
             */
            $table->string('scope_type', 50)->nullable();
            $table->unsignedBigInteger('scope_id')->nullable();

            /*
             * Human readable export title.
             */
            $table->string('title');

            /*
             * Export filters / report parameters.
             */
            $table->json('filters')->nullable();

            /*
             * Generated file.
             */
            $table->string('file_name')->nullable();
            $table->string('file_path')->nullable();

            /*
             * Queue status.
             */
            $table->enum('status', [
                'queued',
                'processing',
                'completed',
                'failed',
            ])->default('queued');

            /*
             * Exported record count.
             */
            $table->unsignedBigInteger('total_records')->default(0);

            /*
             * Error information.
             */
            $table->text('error_message')->nullable();

            /*
             * Processing timestamps.
             */
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            /*
             * Indexes
             */

            $table->index(
                ['requested_by_type', 'requested_by_id'],
                'exports_requester_index'
            );

            $table->index(
                ['type', 'status'],
                'exports_type_status_index'
            );

            $table->index(
                ['scope_type', 'scope_id'],
                'exports_scope_index'
            );

            $table->index(
                ['scope_type', 'scope_id', 'status'],
                'exports_scope_status_index'
            );

            $table->index(
                'created_at',
                'exports_created_at_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exports');
    }
};
