<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->enum('type', ['cash', 'qr', 'bank_transfer', 'card', 'other'])->default('cash');
            $table->string('bank_name', 100)->nullable();
            $table->string('account_number', 80)->nullable();
            $table->string('account_holder', 150)->nullable();
            $table->string('qr_image_path', 255)->nullable();
            $table->boolean('requires_reference')->default(false);
            $table->enum('applies_to', ['sales', 'purchases', 'both'])->default('both');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->index(['applies_to', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
