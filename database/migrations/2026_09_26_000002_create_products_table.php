<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('products', function (Blueprint $table) {
            $table->id(); $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name',200)->index(); $table->text('description')->nullable();
            $table->string('sku',50)->unique(); $table->decimal('cost_price',10,2); $table->decimal('sale_price',10,2);
            $table->unsignedInteger('stock')->default(0); $table->unsignedInteger('min_stock')->default(0);
            $table->enum('status',['active','inactive'])->default('active'); $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('products'); }
};
