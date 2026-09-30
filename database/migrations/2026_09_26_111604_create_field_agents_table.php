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
        Schema::create('field_agents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('contact_number')->nullable();
            $table->string('csc_id')->nullable()->unique();
            $table->string('sakhi_id')->nullable()->unique();
            $table->foreignId('partner_id')->constrained('partners')->restrictOnDelete();
            $table->foreignId('partner_center_id')->constrained('partner_centers')->restrictOnDelete();
            $table->foreignId('placement_partner_user_id')->constrained('partner_placement_users')->restrictOnDelete();
            $table->string('state');
            $table->string('district');
            $table->string('status')->default('active')->index();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('field_agents');
    }
};
