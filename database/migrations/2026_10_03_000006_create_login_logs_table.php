<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('attempted_username', 100)->index();
            $table->string('ip_address', 45)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->enum('status', ['success', 'failed_credentials', 'failed_inactive_user'])->index();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void {
        Schema::dropIfExists('login_logs');
    }
};
