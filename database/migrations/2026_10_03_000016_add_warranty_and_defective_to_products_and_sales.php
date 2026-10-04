<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('warranty_days')->default(0)->after('sale_price');
            $table->integer('defective_stock')->default(0)->after('stock');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->unsignedInteger('warranty_days')->default(0)->after('unit_price');
            $table->date('warranty_expires_at')->nullable()->after('warranty_days');
            $table->string('serial_number', 100)->nullable()->after('warranty_expires_at');
        });

        Schema::table('cash_shifts', function (Blueprint $table) {
            $table->decimal('total_cash_refunds', 10, 2)->default(0.00)->after('total_cash_sales');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['warranty_days', 'defective_stock']);
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['warranty_days', 'warranty_expires_at', 'serial_number']);
        });

        Schema::table('cash_shifts', function (Blueprint $table) {
            $table->dropColumn('total_cash_refunds');
        });
    }
};
