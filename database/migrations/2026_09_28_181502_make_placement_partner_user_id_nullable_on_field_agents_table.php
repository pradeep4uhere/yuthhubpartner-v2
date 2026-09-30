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

       Schema::table('field_agents', function (Blueprint $table) {
            $table->dropForeign(['placement_partner_user_id']);
        });
    
         Schema::table('field_agents', function (Blueprint $table) {
            $table->unsignedBigInteger('placement_partner_user_id')
                ->nullable()
                ->change();

            $table->foreign('placement_partner_user_id')
                ->references('id')
                ->on('partner_placement_users')
                ->nullOnDelete();
        });
        
    }

    /**
     * Reverse the migrations.
     */
   public function down(): void
{
    Schema::table('field_agents', function (Blueprint $table) {
        $table->dropForeign(['placement_partner_user_id']);
    });

    Schema::table('field_agents', function (Blueprint $table) {
        $table->unsignedBigInteger('placement_partner_user_id')
            ->nullable(false)
            ->change();

        $table->foreign('placement_partner_user_id')
            ->references('id')
            ->on('partner_placement_users');
    });
}
};
