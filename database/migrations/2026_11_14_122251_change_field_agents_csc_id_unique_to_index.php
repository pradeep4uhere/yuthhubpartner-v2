<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convert the unique csc_id index into a normal index.
     * Keep sakhi_id uniqueness unchanged.
     */
    public function up(): void
    {
        Schema::table('field_agents', function (Blueprint $table) {
            $table->dropUnique('field_agents_csc_id_unique');
            $table->index('csc_id', 'field_agents_csc_id_index');
        });
    }

    /**
     * Restore the unique csc_id index only when no duplicates exist.
     */
    public function down(): void
    {
        $hasDuplicateCscIds = DB::table('field_agents')
            ->select('csc_id')
            ->whereNotNull('csc_id')
            ->groupBy('csc_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicateCscIds) {
            throw new \RuntimeException(
                'Cannot rollback: duplicate csc_id values exist in field_agents.'
            );
        }

        Schema::table('field_agents', function (Blueprint $table) {
            $table->dropIndex('field_agents_csc_id_index');
            $table->unique('csc_id', 'field_agents_csc_id_unique');
        });
    }
};
