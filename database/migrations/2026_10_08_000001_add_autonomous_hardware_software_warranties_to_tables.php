<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('warranty_hardware_days')->default(0)->after('sale_price');
            $table->unsignedInteger('warranty_software_days')->default(0)->after('warranty_hardware_days');
        });

        // Copiar valores preexistentes de warranty_days a warranty_hardware_days para retrocompatibilidad
        if (Schema::hasColumn('products', 'warranty_days')) {
            DB::table('products')->update([
                'warranty_hardware_days' => DB::raw('warranty_days'),
            ]);
        }

        Schema::table('sale_items', function (Blueprint $table) {
            $table->unsignedInteger('warranty_hardware_days')->default(0)->after('unit_price');
            $table->date('warranty_hardware_expires_at')->nullable()->after('warranty_hardware_days');
            $table->unsignedInteger('warranty_software_days')->default(0)->after('warranty_hardware_expires_at');
            $table->date('warranty_software_expires_at')->nullable()->after('warranty_software_days');
        });

        // Copiar valores preexistentes de warranty_days y warranty_expires_at a las columnas de hardware
        if (Schema::hasColumn('sale_items', 'warranty_days') && Schema::hasColumn('sale_items', 'warranty_expires_at')) {
            DB::table('sale_items')->update([
                'warranty_hardware_days' => DB::raw('warranty_days'),
                'warranty_hardware_expires_at' => DB::raw('warranty_expires_at'),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['warranty_hardware_days', 'warranty_software_days']);
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn([
                'warranty_hardware_days',
                'warranty_hardware_expires_at',
                'warranty_software_days',
                'warranty_software_expires_at',
            ]);
        });
    }
};
