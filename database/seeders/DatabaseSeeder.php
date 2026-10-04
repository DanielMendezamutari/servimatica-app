<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            PaymentMethodSeeder::class,
            CompanySettingSeeder::class,
        ]);
        User::firstOrCreate(['username' => 'admin'], [
            'name' => 'Administrador Dueño',
            'email' => 'admin@servimatica.com',
            'password' => Hash::make('password'),
            'pin_code' => Hash::make('1234'),
            'role' => 'dueno',
            'status' => 'active',
        ]);
    }
}
