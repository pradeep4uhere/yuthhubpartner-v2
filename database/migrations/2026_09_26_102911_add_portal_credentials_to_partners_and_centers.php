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
        Schema::table('partners', function (Blueprint $table): void {
            $table->string('password')->nullable();
            $table->rememberToken();
        });

        Schema::table('partner_centers', function (Blueprint $table): void {
            $table->string('password')->nullable();
            $table->rememberToken();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partner_centers', function (Blueprint $table): void {
            $table->dropRememberToken();
            $table->dropColumn('password');
        });

        Schema::table('partners', function (Blueprint $table): void {
            $table->dropRememberToken();
            $table->dropColumn('password');
        });
    }
};
