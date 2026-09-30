<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partner_centers', function (Blueprint $table) {
            $table->dropUnique('partner_centers_partner_code_unique');

            $table->index(
                'partner_code',
                'idx_partner_centers_partner_code'
            );
        });
    }

    public function down(): void
    {
        Schema::table('partner_centers', function (Blueprint $table) {
            $table->dropIndex('idx_partner_centers_partner_code');

            $table->unique(
                'partner_code',
                'partner_centers_partner_code_unique'
            );
        });
    }
};