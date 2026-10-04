<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 30)->unique();
            $table->foreignId('quote_id')->nullable()->constrained('quotes')->nullOnDelete();
            $table->foreignId('seller_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('client_name', 150)->default('Cliente Mostrador');
            $table->string('client_nit_ci', 30)->nullable();
            $table->foreignId('cash_shift_id')->constrained('cash_shifts')->restrictOnDelete();
            $table->enum('payment_method', ['efectivo', 'qr', 'transferencia'])->default('efectivo');
            $table->decimal('subtotal', 10, 2)->default(0.00);
            $table->decimal('discount_amount', 10, 2)->default(0.00);
            $table->decimal('total_amount', 10, 2)->default(0.00);
            $table->decimal('cash_tendered', 10, 2)->nullable();
            $table->decimal('change_due', 10, 2)->nullable()->default(0.00);
            $table->decimal('commission_rate', 5, 2)->default(0.00);
            $table->decimal('commission_amount', 10, 2)->default(0.00);
            $table->enum('status', ['completed', 'cancelled'])->default('completed')->index();
            $table->string('cancellation_reason', 255)->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['created_at', 'status']);
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('product_name', 200);
            $table->string('product_sku', 50);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_cost', 10, 2)->default(0.00);
            $table->decimal('unit_price', 10, 2)->default(0.00);
            $table->decimal('subtotal', 10, 2)->default(0.00);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};
