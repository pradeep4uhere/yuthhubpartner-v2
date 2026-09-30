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
        Schema::create('event_category_master', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('event_master_type_id');

            $table->string('name');

            $table->boolean('status')->default(1);

            $table->timestamps();

            $table->foreign('event_master_type_id')
                ->references('id')
                ->on('event_master_type')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->index('event_master_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_category_master');
    }
};
