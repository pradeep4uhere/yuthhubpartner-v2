<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add record progress and ETA fields to export jobs.
     */
    public function up(): void
    {
        Schema::table('exports', function (Blueprint $table) {
            $table->unsignedBigInteger('processed_records')
                ->default(0);

            $table->unsignedInteger('estimated_seconds_remaining')
                ->nullable();
        });
    }

    /**
     * Remove export progress and ETA fields during rollback.
     */
    public function down(): void
    {
        Schema::table('exports', function (Blueprint $table) {
            $table->dropColumn([
                'processed_records',
                'estimated_seconds_remaining',
            ]);
        });
    }
};
