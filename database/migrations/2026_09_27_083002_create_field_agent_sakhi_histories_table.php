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
        Schema::create('field_agent_sakhi_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('field_agent_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('old_sakhi_id');
            $table->string('new_sakhi_id');

            $table->unsignedBigInteger('changed_by')->nullable();

            $table->timestamp('changed_at');

            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->index('field_agent_id');
            $table->index('old_sakhi_id');
            $table->index('new_sakhi_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('field_agent_sakhi_histories');
    }
};
