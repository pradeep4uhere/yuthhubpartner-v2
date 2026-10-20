<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Create configurable categories for the Support Ticket
     * Management System.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('support_ticket_categories', function (Blueprint $table) {
            $table->id();

            $table->string('name', 100)->unique();

            $table->text('description')->nullable();

            $table->boolean('is_active')
                ->default(true)
                ->index();

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drop the support ticket categories table.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('support_ticket_categories');
    }
};
