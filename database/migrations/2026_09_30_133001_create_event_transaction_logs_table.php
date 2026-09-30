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
        Schema::create('event_transaction_logs', function (Blueprint $table) {
             $table->id();

            $table->unsignedBigInteger('event_transaction_id')->index();

            /*
            |--------------------------------------------------------------------------
            | Action
            |--------------------------------------------------------------------------
            */
            $table->string('action')->index();

            /*
            |--------------------------------------------------------------------------
            | Previous / New workflow state
            |--------------------------------------------------------------------------
            */
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Actor who performed the action
            | frontend = Field Agent / Partner / Placement User
            | backend  = Admin User
            | system   = Automated process / Cron / Job
            |--------------------------------------------------------------------------
            */
            $table->string('actor_type')->default('frontend')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_type')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Action details
            |--------------------------------------------------------------------------
            */
            $table->text('comment')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Optional change tracking
            |--------------------------------------------------------------------------
            */
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Request / system information
            |--------------------------------------------------------------------------
            */
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            $table->foreign('event_transaction_id')
                ->references('id')
                ->on('event_transactions')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_transaction_logs');
    }
};
