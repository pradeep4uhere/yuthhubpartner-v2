
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('learners', function (Blueprint $table) {

            // CSC-wise learners
            $table->index(
                'unit',
                'idx_learners_unit'
            );

            // Partner + Status
            $table->index(
                ['partner_id', 'status'],
                'idx_learners_partner_status'
            );

            // Partner + Created Date
            $table->index(
                ['partner_id', 'created_at'],
                'idx_learners_partner_created'
            );

            // Field Agent + Status
            $table->index(
                ['field_agent_id', 'status'],
                'idx_learners_agent_status'
            );

            // Center + Status
            $table->index(
                ['partner_center_id', 'status'],
                'idx_learners_center_status'
            );

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learners', function (Blueprint $table) {

            $table->dropIndex('idx_learners_unit');

            $table->dropIndex('idx_learners_partner_status');

            $table->dropIndex('idx_learners_partner_created');

            $table->dropIndex('idx_learners_agent_status');

            $table->dropIndex('idx_learners_center_status');

        });
    }
};
