<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('username', 50)->unique();
            $table->string('email', 150)->unique();
            $table->string('password');
            $table->string('pin_code');
            $table->enum('role', ['dueno', 'vendedor']);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
