<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_number', 30)->unique();
            $table->string('invoice_number', 50)->index();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->date('purchase_date')->index();
            $table->enum('payment_condition', ['contado', 'credito'])->default('contado');
            $table->enum('payment_method', ['efectivo', 'transferencia', 'otro'])->default('transferencia');
            $table->enum('payment_status', ['pagado', 'pendiente'])->default('pagado')->index();
            $table->date('due_date')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0.00);
            $table->decimal('total_amount', 12, 2)->default(0.00);
            $table->enum('status', ['received', 'cancelled'])->default('received')->index();
            $table->string('cancellation_reason', 255)->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['created_at', 'status']);
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('product_name', 200);
            $table->string('product_sku', 50);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_cost', 10, 2)->default(0.00);
            $table->decimal('subtotal', 12, 2)->default(0.00);
            $table->decimal('previous_cost', 10, 2)->nullable();
            $table->decimal('previous_sale_price', 10, 2)->nullable();
            $table->decimal('new_sale_price', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
    }
};
