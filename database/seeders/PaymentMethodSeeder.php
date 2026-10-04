<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'name' => 'Efectivo en Mostrador',
                'type' => 'cash',
                'bank_name' => null,
                'account_number' => null,
                'account_holder' => null,
                'qr_image_path' => null,
                'requires_reference' => false,
                'applies_to' => 'both',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pago QR Simple (Bancos / Billeteras)',
                'type' => 'qr',
                'bank_name' => 'Banco Unión',
                'account_number' => '10000012345678',
                'account_holder' => 'Servimática Bolivia',
                'qr_image_path' => null,
                'requires_reference' => true,
                'applies_to' => 'sales',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Transferencia Bancaria Directa',
                'type' => 'bank_transfer',
                'bank_name' => 'Banco BCP',
                'account_number' => '201-5049382-0-12',
                'account_holder' => 'Servimática Bolivia S.R.L.',
                'qr_image_path' => null,
                'requires_reference' => true,
                'applies_to' => 'both',
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($methods as $method) {
            DB::table('payment_methods')->updateOrInsert(
                ['name' => $method['name']],
                $method
            );
        }
    }
}
