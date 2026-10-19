
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add MPIN authentication fields to Field Agents.
     *
     * MPIN is stored as a hash, never as plain text.
     * MPIN login remains disabled by default for all agents.
     */
    public function up(): void
    {
        Schema::table('field_agents', function (Blueprint $table) {
            $table->string('mpin_hash')->nullable();

            $table->boolean('mpin_login_enabled')
                ->default(false);
        });
    }

    /**
     * Remove the MPIN authentication fields.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('field_agents', function (Blueprint $table) {
            $table->dropColumn([
                'mpin_hash',
                'mpin_login_enabled',
            ]);
        });
    }
};
