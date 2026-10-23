
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_transactions', function (Blueprint $table) {
            $table->index(
                [
                    'partner_id',
                    'workflow_status',
                    'event_master_type_id',
                    'field_agent_id',
                ],
                'et_agent_listing_idx'
            );
        });

        Schema::table('field_agents', function (Blueprint $table) {
            $table->index(
                [
                    'partner_id',
                    'state',
                    'district',
                    'partner_center_id',
                ],
                'fa_partner_filters_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('event_transactions', function (Blueprint $table) {
            $table->dropIndex('et_agent_listing_idx');
        });

        Schema::table('field_agents', function (Blueprint $table) {
            $table->dropIndex('fa_partner_filters_idx');
        });
    }
};
