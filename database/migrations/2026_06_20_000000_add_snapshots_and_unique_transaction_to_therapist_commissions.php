<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('therapist_commissions', function (Blueprint $table) {
            $table->foreignId('therapist_user_id')
                ->nullable()
                ->after('therapist_profile_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->decimal('commission_base_amount', 10, 2)
                ->default(0)
                ->after('commission_rate');
            $table->unique('transaction_id', 'commissions_transaction_unique');
        });
    }

    public function down(): void
    {
        Schema::table('therapist_commissions', function (Blueprint $table) {
            $table->dropUnique('commissions_transaction_unique');
            $table->dropConstrainedForeignId('therapist_user_id');
            $table->dropColumn('commission_base_amount');
        });
    }
};
