<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('payment_method_id')->nullable()->after('payment_method')->constrained('payment_methods')->nullOnDelete();
            $table->string('reference_number', 100)->nullable()->after('payment_method_id');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('payment_method_id')->nullable()->after('payment_method')->constrained('payment_methods')->nullOnDelete();
            $table->string('reference_number', 100)->nullable()->after('payment_method_id');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->foreignId('payment_method_id')->nullable()->after('valid_until')->constrained('payment_methods')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['payment_method_id']);
            $table->dropColumn(['payment_method_id', 'reference_number']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['payment_method_id']);
            $table->dropColumn(['payment_method_id', 'reference_number']);
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign(['payment_method_id']);
            $table->dropColumn(['payment_method_id']);
        });
    }
};
