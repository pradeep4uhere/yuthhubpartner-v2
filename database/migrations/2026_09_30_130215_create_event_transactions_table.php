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
        Schema::create('event_transactions', function (Blueprint $table) {
            $table->id();
            /*
            |--------------------------------------------------------------------------
            | Relationships
            |--------------------------------------------------------------------------
            */
            $table->unsignedBigInteger('learner_id')->nullable()->index();
            $table->unsignedBigInteger('partner_id')->nullable()->index();
            $table->unsignedBigInteger('partner_center_id')->nullable()->index();
            $table->unsignedBigInteger('partner_placement_user_id')->nullable()->index();
            $table->unsignedBigInteger('field_agent_id')->nullable()->index();

            /*
            |--------------------------------------------------------------------------
            | Review / Workflow
            |--------------------------------------------------------------------------
            */
            $table->string('review_status')->nullable()->index();
            $table->string('workflow_status')->default('draft')->index();

            /*
            |--------------------------------------------------------------------------
            | Beneficiary
            |--------------------------------------------------------------------------
            */
            $table->string('beneficiary_phone_number')->nullable();
            $table->string('beneficiary_name')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Event
            |--------------------------------------------------------------------------
            */
            $table->string('event_master_id')->nullable()->index();
            $table->string('event_master_type_id')->nullable()->index();

            $table->string('event_type_name')->nullable();
            $table->string('event_category_name')->nullable();

            $table->date('event_create_date')->nullable();
            $table->date('event_date_submitted')->nullable()->index();

            $table->decimal('event_value', 10, 2)->nullable();

            $table->unsignedBigInteger('field_type_id')->nullable();
            $table->unsignedBigInteger('industry_type_id')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Documents / Comments
            |--------------------------------------------------------------------------
            */
            $table->text('uploaded_doc_1')->nullable();
            $table->text('uploaded_doc_2')->nullable();
            $table->text('uploaded_doc_3')->nullable();
            $table->text('last_comment')->nullable();

             /*
            |--------------------------------------------------------------------------
            | Documents For Old Event Transactions
            |--------------------------------------------------------------------------
            */
            $table->text('uploaded_doc_links')->nullable();

            $table->boolean('status')->default(1);

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_transactions');
    }
};
