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
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('guest_name')->nullable()->after('customer_profile_id');
            $table->string('guest_contact', 30)->nullable()->after('guest_name');
            $table->boolean('is_walk_in')->default(false)->after('guest_contact');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['guest_name', 'guest_contact', 'is_walk_in']);
        });
    }
};
