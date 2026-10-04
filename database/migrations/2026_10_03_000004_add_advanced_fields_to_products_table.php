<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('subfamily_id')
                ->nullable()
                ->after('category_id')
                ->constrained('categories')
                ->onDelete('set null');

            $table->foreignId('brand_id')
                ->nullable()
                ->after('subfamily_id')
                ->constrained('brands')
                ->onDelete('restrict');

            $table->foreignId('product_model_id')
                ->nullable()
                ->after('brand_id')
                ->constrained('product_models')
                ->onDelete('restrict');

            $table->enum('condition', ['nuevo', 'open_box', 'usado', 'reacondicionado'])
                ->default('nuevo')
                ->after('product_model_id');
        });
    }

    public function down(): void {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['subfamily_id']);
            $table->dropForeign(['brand_id']);
            $table->dropForeign(['product_model_id']);
            $table->dropColumn(['subfamily_id', 'brand_id', 'product_model_id', 'condition']);
        });
    }
};
