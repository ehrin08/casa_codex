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
        Schema::create('customer_rfm_scores', function (Blueprint $table) {
            // Analytics and promotion eligibility will use these periodic RFM snapshots.
            $table->id();
            $table->foreignId('customer_profile_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('recency_score');
            $table->unsignedTinyInteger('frequency_score');
            $table->unsignedTinyInteger('monetary_score');
            $table->string('segment_label')->index();
            $table->date('calculated_at')->index();
            $table->text('source_notes')->nullable();
            $table->timestamps();

            $table->unique(['customer_profile_id', 'calculated_at'], 'customer_rfm_customer_date_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_rfm_scores');
    }
};
