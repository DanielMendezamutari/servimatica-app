<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->char('public_token', 36)->nullable()->after('quote_number');
        });

        // Poblar UUIDs para registros existentes
        $quotes = DB::table('quotes')->get();
        foreach ($quotes as $q) {
            DB::table('quotes')->where('id', $q->id)->update([
                'public_token' => (string) Str::uuid(),
            ]);
        }

        Schema::table('quotes', function (Blueprint $table) {
            $table->unique('public_token');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropUnique(['public_token']);
            $table->dropColumn('public_token');
        });
    }
};
