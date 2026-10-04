<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->string('trade_name', 120)->default('Servimática PC');
            $table->string('legal_name', 150)->nullable();
            $table->string('tax_id', 30)->nullable();
            $table->string('slogan', 200)->nullable();
            $table->string('branch_name', 100)->default('Sucursal Central');
            $table->string('city', 100)->default('Beni — Bolivia');
            $table->string('address', 250)->default('Calle Principal');
            $table->string('mobile', 30)->default('77000000');
            $table->string('phone', 30)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('logo_path', 255)->nullable();
            $table->text('default_quote_terms')->nullable();
            $table->text('receipt_footer_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
