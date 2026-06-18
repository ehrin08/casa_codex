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
        Schema::create('promotions', function (Blueprint $table) {
            // Future RFM and rule-based promotion modules will evaluate the rule fields here.
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('discount_type', 30);
            $table->decimal('discount_value', 10, 2);
            $table->string('rfm_segment_label')->nullable()->index();
            $table->unsignedTinyInteger('rule_min_recency_score')->nullable();
            $table->unsignedTinyInteger('rule_min_frequency_score')->nullable();
            $table->unsignedTinyInteger('rule_min_monetary_score')->nullable();
            $table->json('rule_payload')->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->string('status', 30)->default('inactive')->index();
            $table->timestamps();

            $table->index(['starts_at', 'ends_at'], 'promotions_active_dates_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
