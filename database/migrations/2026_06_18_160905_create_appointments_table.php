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
        Schema::create('appointments', function (Blueprint $table) {
            // Central booking record used later by dashboards, transactions, commissions, and reviews.
            $table->id();
            $table->foreignId('customer_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('therapist_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->date('appointment_date')->index();
            $table->time('start_time');
            $table->time('end_time');
            $table->string('status', 30)->default('pending')->index();
            $table->string('service_name_snapshot')->nullable();
            $table->unsignedSmallInteger('service_duration_minutes_snapshot')->nullable();
            $table->decimal('service_price_snapshot', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['therapist_profile_id', 'appointment_date'], 'appointments_therapist_date_idx');
            $table->index(['customer_profile_id', 'appointment_date'], 'appointments_customer_date_idx');
            $table->index(['appointment_date', 'status'], 'appointments_date_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
