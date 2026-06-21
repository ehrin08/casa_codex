<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('paid_by_user_id')
                ->nullable()
                ->after('cashier_user_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->dateTime('paid_at')->nullable()->after('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('paid_by_user_id');
            $table->dropColumn('paid_at');
        });
    }
};
