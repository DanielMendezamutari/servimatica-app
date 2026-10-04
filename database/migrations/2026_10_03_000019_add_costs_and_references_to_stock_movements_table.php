<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->decimal('unit_cost', 12, 4)->nullable()->after('reason');
            $table->decimal('total_cost', 12, 2)->nullable()->after('unit_cost');
            $table->string('reference_type', 50)->nullable()->after('total_cost');
            $table->unsignedBigInteger('reference_id')->nullable()->after('reference_type');

            $table->index(['product_id', 'created_at', 'id']);
            $table->index(['created_at', 'reference_type']);
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'created_at', 'id']);
            $table->dropIndex(['created_at', 'reference_type']);
            $table->dropColumn(['unit_cost', 'total_cost', 'reference_type', 'reference_id']);
        });
    }
};
