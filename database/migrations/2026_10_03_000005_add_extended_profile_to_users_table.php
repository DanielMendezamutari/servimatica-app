<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->string('ci', 30)->nullable()->index()->after('name');
            $table->string('phone', 30)->nullable()->after('email');
            $table->string('address', 255)->nullable()->after('phone');
            $table->enum('gender', ['masculino', 'femenino', 'otro'])->nullable()->after('address');
            $table->decimal('sales_commission', 5, 2)->default(0.00)->after('gender');
            $table->string('branch', 100)->default('Casa Matriz')->after('sales_commission');
            $table->string('avatar', 255)->nullable()->after('branch');
        });
    }

    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'ci',
                'phone',
                'address',
                'gender',
                'sales_commission',
                'branch',
                'avatar',
            ]);
        });
    }
};
