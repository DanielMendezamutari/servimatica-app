<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\BrandModel;
use App\Infrastructure\Persistence\Eloquent\CashShiftModel;
use App\Infrastructure\Persistence\Eloquent\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\ClientModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Persistence\Eloquent\SupplierModel;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder Completo de Demostración:
 * Incluye datos de tienda real, usuarios (Dueño y Vendedor), formas de pago,
 * categorías, marcas, catálogo completo de laptops (baja, media, alta),
 * periféricos, repuestos, proveedores, clientes CRM y caja abierta.
 */
class DemoStoreSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Configuración de la Empresa (Servimática PC - Comercial Chiriguano)
        $this->call(CompanySettingSeeder::class);
        DB::table('company_settings')->where('id', 1)->update([
            'trade_name' => 'Servimática PC',
            'legal_name' => 'Servimática Computación S.R.L.',
            'slogan' => 'Especialistas en Laptops de Gama Alta, Media, Baja y Servicio Técnico Certificado',
            'branch_name' => 'Sucursal Central Chiriguano',
            'city' => 'Santa Cruz de la Sierra — Bolivia',
            'address' => 'Comercial Chiriguano, Pasillo 2, Local # 333',
            'mobile' => '67369293',
            'email' => 'contacto@servimatica.com',
            'updated_at' => now(),
        ]);

        // 2. Formas de Pago
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

        // 3. Usuarios de la Tienda (Dueño y Vendedor/Cajero)
        $owner = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Ing. Administrador Servimática',
                'email' => 'admin@servimatica.com',
                'password' => Hash::make('password'),
                'pin_code' => Hash::make('1234'),
                'role' => 'dueno',
                'status' => 'active',
                'phone' => '67369293',
                'address' => 'Comercial Chiriguano Pasillo 2 Local 333',
                'branch' => 'Sucursal Chiriguano',
            ]
        );

        $seller = User::firstOrCreate(
            ['username' => 'vendedor'],
            [
                'name' => 'Javier Vendedor Chiriguano',
                'email' => 'vendedor@servimatica.com',
                'password' => Hash::make('password'),
                'pin_code' => Hash::make('4321'),
                'role' => 'vendedor',
                'status' => 'active',
                'phone' => '78012345',
                'address' => 'Barrio Ramafa',
                'branch' => 'Sucursal Chiriguano',
                'sales_commission' => 2.00,
            ]
        );

        // 4. Categorías de la Tienda
        $this->call(CategorySeeder::class);
        $catHardware = CategoryModel::firstOrCreate(
            ['name' => 'Componentes y Hardware'],
            ['description' => 'Discos SSD, Memorias RAM, Procesadores y Fuentes', 'status' => 'active']
        );
        $catPerifericos = CategoryModel::firstOrCreate(
            ['name' => 'Periféricos y Gaming'],
            ['description' => 'Mouses, Teclados, Auriculares y Monitores', 'status' => 'active']
        );
        $catServicio = CategoryModel::firstOrCreate(
            ['name' => 'Servicio Técnico'],
            ['description' => 'Mantenimiento, formateo y reparación', 'status' => 'active']
        );

        // 5. Marcas
        $brands = [
            'Lenovo', 'ASUS', 'HP', 'Dell', 'Acer', 'MSI', 'Kingston', 'Logitech', 'Redragon', 'Corsair',
        ];
        foreach ($brands as $b) {
            BrandModel::firstOrCreate(['name' => $b], ['is_active' => true]);
        }

        // 6. Catálogo de Laptops (Gama Baja, Media y Alta)
        $this->call(LaptopTiersCatalogSeeder::class);

        // 7. Productos adicionales (Periféricos y Componentes populares)
        $additionalProducts = [
            [
                'sku' => 'ACC-SSD-001',
                'name' => 'Disco Sólido Kingston NV2 1TB M.2 PCIe 4.0 NVMe',
                'brand' => 'Kingston',
                'category_id' => $catHardware->id,
                'condition' => 'nuevo',
                'cost_price' => 450.00,
                'sale_price' => 590.00,
                'stock' => 12,
                'min_stock' => 2,
                'warranty_days' => 365,
                'status' => 'active',
                'image_path' => '/images/placeholders/laptop.svg',
                'description' => 'Velocidades de lectura hasta 3500MB/s. Actualización ideal para laptops y PCs de escritorio con factor de forma M.2 2280.',
            ],
            [
                'sku' => 'ACC-MOU-001',
                'name' => 'Mouse Gamer Logitech G203 Lightsync RGB 8000 DPI',
                'brand' => 'Logitech',
                'category_id' => $catPerifericos->id,
                'condition' => 'nuevo',
                'cost_price' => 140.00,
                'sale_price' => 195.00,
                'stock' => 15,
                'min_stock' => 3,
                'warranty_days' => 180,
                'status' => 'active',
                'image_path' => '/images/placeholders/laptop.svg',
                'description' => 'Sensor para juegos de 8.000 DPI con iluminación RGB Lightsync personalizable y 6 botones programables.',
            ],
            [
                'sku' => 'ACC-TEC-001',
                'name' => 'Teclado Mecánico Redragon Kumara K552 RGB Switches Red',
                'brand' => 'Redragon',
                'category_id' => $catPerifericos->id,
                'condition' => 'nuevo',
                'cost_price' => 220.00,
                'sale_price' => 290.00,
                'stock' => 8,
                'min_stock' => 2,
                'warranty_days' => 180,
                'status' => 'active',
                'image_path' => '/images/placeholders/laptop.svg',
                'description' => 'Teclado mecánico compacto TKL resistente a salpicaduras con retroiluminación RGB completa y switches lineales silenciosos.',
            ],
            [
                'sku' => 'SER-TEC-001',
                'name' => 'Mantenimiento Preventivo y Limpieza de Laptop + Pasta Térmica Arctic MX-4',
                'brand' => null,
                'category_id' => $catServicio->id,
                'condition' => 'nuevo',
                'cost_price' => 30.00,
                'sale_price' => 120.00,
                'stock' => 999,
                'min_stock' => 0,
                'warranty_days' => 30,
                'status' => 'active',
                'image_path' => '/images/placeholders/laptop.svg',
                'description' => 'Limpieza integral de ventiladores, disipadores de cobre, cambio de pasta térmica de alto rendimiento Arctic MX-4 y optimización de sistema operativo.',
            ],
        ];

        foreach ($additionalProducts as $p) {
            $brandName = $p['brand'] ?? null;
            unset($p['brand']);
            $brand = $brandName ? BrandModel::where('name', $brandName)->first() : null;
            $p['brand_id'] = $brand?->id;

            ProductModel::updateOrCreate(
                ['sku' => $p['sku']],
                $p
            );
        }

        // 8. Proveedores Reales de Tecnología
        $suppliers = [
            [
                'name' => 'Distribuidora Mayorista Chiriguano',
                'nit' => '1023847021',
                'contact_name' => 'Lic. Roberto Aguilera',
                'phone' => '71002233',
                'email' => 'ventas@chiriguanomayorista.bo',
                'city' => 'Santa Cruz',
                'address' => 'Comercial Chiriguano Pasillo Central #120',
                'is_active' => true,
            ],
            [
                'name' => 'Ingram Micro Bolivia',
                'nit' => '1002938475',
                'contact_name' => 'Ing. Claudia Méndez',
                'phone' => '33458900',
                'email' => 'pedidos@ingrammicro.com.bo',
                'city' => 'Santa Cruz',
                'address' => 'Parque Industrial PI-24 Mz. 10',
                'is_active' => true,
            ],
            [
                'name' => 'Importadora TechBolivia S.R.L.',
                'nit' => '1029381726',
                'contact_name' => 'Marcos Torrez',
                'phone' => '72033445',
                'email' => 'contacto@techbolivia.bo',
                'city' => 'La Paz',
                'address' => 'Av. 16 de Julio, Edif. Alameda #14',
                'is_active' => true,
            ],
        ];

        foreach ($suppliers as $s) {
            SupplierModel::firstOrCreate(['name' => $s['name']], $s);
        }

        // 9. Clientes CRM
        $clients = [
            [
                'name' => 'Carlos Montaño Justiniano',
                'nit_ci' => '8392102 SCZ',
                'phone' => '76012345',
                'email' => 'carlos.montano@gmail.com',
                'address' => 'Av. Bush 2do anillo #450',
                'client_type' => 'final',
                'city' => 'Santa Cruz',
                'notes' => 'Cliente entusiasta del gaming, compró laptop Legion en junio.',
                'is_active' => true,
            ],
            [
                'name' => 'Constructora Oriente S.R.L.',
                'nit_ci' => '1029384756',
                'phone' => '33456789',
                'email' => 'adquisiciones@constructoraoriente.bo',
                'address' => 'Equipetrol Calle 8 Este #12',
                'client_type' => 'empresa',
                'city' => 'Santa Cruz',
                'notes' => 'Facturación recurrente para equipos de diseño y Render AutoCAD.',
                'is_active' => true,
            ],
            [
                'name' => 'Ing. Andrea Vaca Díez',
                'nit_ci' => '5948372 SCZ',
                'phone' => '77312345',
                'email' => 'andrea.vacadiez@hotmail.com',
                'address' => 'Barrio Sirari, Calle Los Claveles #22',
                'client_type' => 'frecuente',
                'city' => 'Santa Cruz',
                'notes' => 'Docente universitaria, solicita cotizaciones de laptops corporativas.',
                'is_active' => true,
            ],
        ];

        foreach ($clients as $c) {
            ClientModel::firstOrCreate(['name' => $c['name']], $c);
        }

        // 10. Turno de Caja Abierto para el Vendedor (Listo para operar POS de inmediato)
        if ($seller) {
            CashShiftModel::firstOrCreate(
                ['user_id' => $seller->id, 'status' => 'open'],
                [
                    'opened_at' => now(),
                    'opening_amount' => 300.00,
                    'total_cash_sales' => 0.00,
                    'total_qr_sales' => 0.00,
                    'closing_amount' => null,
                    'expected_amount' => 300.00,
                    'difference' => null,
                    'notes' => 'Caja inicial abierta para jornada en mostrador Comercial Chiriguano.',
                ]
            );
        }
    }
}
