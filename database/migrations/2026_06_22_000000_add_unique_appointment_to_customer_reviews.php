<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_reviews', function (Blueprint $table) {
            $table->unique('appointment_id', 'customer_reviews_appointment_unique');
            $table->index('rating', 'customer_reviews_rating_index');
        });
    }

    public function down(): void
    {
        Schema::table('customer_reviews', function (Blueprint $table) {
            $table->dropIndex('customer_reviews_rating_index');
            $table->dropUnique('customer_reviews_appointment_unique');
        });
    }
};
