<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add MPIN settings columns for Placement Partner accounts.
     */
    public function up(): void
    {
        Schema::table('partner_placement_users', function (Blueprint $table) {
            $table->string('mpin_hash')->nullable();
            $table->boolean('mpin_login_enabled')->default(false);
        });
    }

    /**
     * Remove the MPIN settings columns if this migration is rolled back.
     */
    public function down(): void
    {
        Schema::table('partner_placement_users', function (Blueprint $table) {
            $table->dropColumn([
                'mpin_hash',
                'mpin_login_enabled',
            ]);
        });
    }
};
