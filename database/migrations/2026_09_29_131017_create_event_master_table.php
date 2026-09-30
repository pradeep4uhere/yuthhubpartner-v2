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
        Schema::create('event_master', function (Blueprint $table) {
             $table->id();

            $table->unsignedBigInteger('event_master_type_id');

            $table->unsignedBigInteger('event_category_id');

            $table->string('event_name');

            $table->text('description')->nullable();

            $table->string('eligibility')->nullable();

            $table->decimal('fees_per_transactions', 10, 2)
                ->nullable();

            $table->string('document_name_1')->nullable();
            $table->string('document_name_2')->nullable();
            $table->string('document_name_3')->nullable();

            $table->boolean('status')->default(1);

            $table->timestamps();

            // Foreign Keys
            $table->foreign('event_master_type_id')
                ->references('id')
                ->on('event_master_type')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->foreign('event_category_id')
                ->references('id')
                ->on('event_category_master')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            // Indexes
            $table->index('event_master_type_id');
            $table->index('event_category_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_master');
    }
};
