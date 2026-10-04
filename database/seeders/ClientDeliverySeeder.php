<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\BrandModel;
use App\Infrastructure\Persistence\Eloquent\CategoryModel;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder de Entrega a Producción / Cliente:
 * Base de datos limpia con UN SOLO usuario administrador dueño,
 * información oficial de la tienda de Servimática en el Comercial Chiriguano,
 * formas de pago base, categorías y marcas de tecnología configuradas.
 * CERO productos demo, CERO ventas falsas, CERO clientes ficticios.
 */
class ClientDeliverySeeder extends Seeder
{
    public function run(): void
    {
        // 1. Configuración Oficial de la Empresa
        $this->call(CompanySettingSeeder::class);
        DB::table('company_settings')->where('id', 1)->update([
            'trade_name' => 'Servimática PC',
            'legal_name' => 'Servimática Computación S.R.L.',
            'tax_id' => null,
            'slogan' => 'Venta de Laptops, Insumos de Computación y Servicio Técnico Especializado',
            'branch_name' => 'Sucursal Comercial Chiriguano',
            'city' => 'Santa Cruz de la Sierra — Bolivia',
            'address' => 'Comercial Chiriguano, Pasillo 2, Local # 333',
            'mobile' => '67369293',
            'phone' => null,
            'email' => 'contacto@servimatica.com',
            'default_quote_terms' => "• Precios expresados en Bolivianos (Bs.), válidos por 5 días hábiles a partir de la fecha de emisión.\n• Equipos 100% nuevos en caja sellada con garantía técnica oficial.\n• Entrega inmediata en tienda o envíos a nivel nacional previo acuerdo.",
            'receipt_footer_message' => '¡Gracias por su compra en Servimática PC! Conserve este comprobante para validar su garantía técnica.',
            'warranty_terms' => "• La garantía técnica cubre exclusivamente fallas y defectos de fabricación por el plazo pactado en este comprobante.\n• La garantía queda automáticamente invalidada por daños físicos (golpes, caídas, quiñes), variaciones o sobrecargas eléctricas, humedad, contacto con líquidos, o si los sellos de seguridad han sido alterados o removidos.\n• Para hacer efectiva la garantía es requisito indispensable presentar este comprobante y el equipo con sus números de serie legibles.",
            'updated_at' => now(),
        ]);

        // 2. Formas de Pago Estándar de Bolivia
        $this->call(PaymentMethodSeeder::class);
        DB::table('payment_methods')->updateOrInsert(
            ['name' => 'Tarjeta de Débito / Crédito'],
            [
                'type' => 'card',
                'bank_name' => 'Red Enlace / Banco',
                'account_number' => null,
                'account_holder' => null,
                'qr_image_path' => null,
                'requires_reference' => true,
                'applies_to' => 'sales',
                'sort_order' => 4,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 3. UN SOLO Usuario Administrador Dueño
        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrador Servimática',
                'email' => 'admin@servimatica.com',
                'password' => Hash::make('password'),
                'pin_code' => Hash::make('1234'),
                'role' => 'dueno',
                'status' => 'active',
                'phone' => '67369293',
                'address' => 'Comercial Chiriguano, Pasillo 2, Local # 333',
                'branch' => 'Sucursal Chiriguano',
            ]
        );

        // 4. Categorías Base de Computación
        $this->call(CategorySeeder::class);
        CategoryModel::firstOrCreate(
            ['name' => 'Componentes y Hardware'],
            ['description' => 'Discos SSD, Memorias RAM, Procesadores y Fuentes', 'status' => 'active']
        );
        CategoryModel::firstOrCreate(
            ['name' => 'Periféricos y Gaming'],
            ['description' => 'Mouses, Teclados, Auriculares y Monitores', 'status' => 'active']
        );
        CategoryModel::firstOrCreate(
            ['name' => 'Servicio Técnico'],
            ['description' => 'Mantenimiento, formateo y reparación', 'status' => 'active']
        );

        // 5. Marcas Base
        $brands = [
            'Lenovo', 'ASUS', 'HP', 'Dell', 'Acer', 'MSI', 'Kingston', 'Logitech', 'Redragon', 'Samsung',
        ];
        foreach ($brands as $b) {
            BrandModel::firstOrCreate(['name' => $b], ['is_active' => true]);
        }
    }
}
