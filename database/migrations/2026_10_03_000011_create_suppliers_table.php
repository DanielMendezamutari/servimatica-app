<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->index();
            $table->string('nit', 30)->nullable()->index();
            $table->string('contact_name', 100)->nullable();
            $table->string('phone', 30)->nullable()->index();
            $table->string('email', 100)->nullable();
            $table->string('city', 50)->nullable();
            $table->string('address', 255)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
