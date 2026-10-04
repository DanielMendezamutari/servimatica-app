<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\BrandModel;
use App\Infrastructure\Persistence\Eloquent\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use Illuminate\Database\Seeder;

class LaptopTiersCatalogSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Asegurar categoría de Laptops
        $laptopCat = CategoryModel::firstOrCreate(
            ['name' => 'Laptops'],
            ['status' => 'active']
        );

        // 2. Marcas
        $brands = [
            'Lenovo' => BrandModel::firstOrCreate(['name' => 'Lenovo'], ['is_active' => true]),
            'HP' => BrandModel::firstOrCreate(['name' => 'HP'], ['is_active' => true]),
            'ASUS' => BrandModel::firstOrCreate(['name' => 'ASUS'], ['is_active' => true]),
            'Dell' => BrandModel::firstOrCreate(['name' => 'Dell'], ['is_active' => true]),
            'Acer' => BrandModel::firstOrCreate(['name' => 'Acer'], ['is_active' => true]),
        ];

        // 3. Catálogo de Laptops por Gamas
        $laptops = [
            // ==================== GAMA BAJA / ECONÓMICAS ====================
            [
                'sku' => 'LAP-LOW-001',
                'name' => 'Laptop Lenovo IdeaPad 1 14IGL7 (Gama de Entrada)',
                'brand' => 'Lenovo',
                'category_id' => $laptopCat->id,
                'condition' => 'nuevo',
                'cost_price' => 2100.00,
                'sale_price' => 2650.00,
                'stock' => 4,
                'min_stock' => 1,
                'warranty_days' => 365,
                'status' => 'active',
                'image_path' => '/images/placeholders/laptop.svg',
                'gallery_images' => [
                    '/images/placeholders/laptop.svg',
                    '/images/placeholders/laptop.svg',
                    '/images/placeholders/laptop.svg',
                ],
                'description' => '[GAMA BAJA / ECONÓMICA] Procesador Intel Celeron N4020, 8GB RAM DDR4, 256GB SSD NVMe ultrarrápido, Pantalla 14" HD antirreflejo, Windows 11 Home, Batería de 8 horas, peso ligero 1.4 kg. Ideal para estudiantes de colegio, tareas escolares, Word, Excel, Zoom y navegación web.',
            ],
            [
                'sku' => 'LAP-LOW-002',
                'name' => 'Laptop HP 250 G9 (Gama Económica Oficina)',
                'brand' => 'HP',
                'category_id' => $laptopCat->id,
                'condition' => 'nuevo',
                'cost_price' => 2800.00,
                'sale_price' => 3450.00,
                'stock' => 5,
                'min_stock' => 1,
                'warranty_days' => 365,
                'status' => 'active',
                'image_path' => '/images/placeholders/laptop.svg',
                'gallery_images' => [
                    '/images/placeholders/laptop.svg',
                    '/images/placeholders/laptop.svg',
                    '/images/placeholders/laptop.svg',
                ],
                'description' => '[GAMA BAJA-MEDIA DE ENTRADA] Procesador Intel Core i3-1215U (6 núcleos hasta 4.4GHz), 8GB RAM DDR4 (expandible a 32GB), 512GB SSD NVMe M.2, Pantalla 15.6" Full HD (1920x1080) con teclado numérico completo. Excelente durabilidad para trabajo administrativo, facturación y clases universitarias.',
            ],
            [
                'sku' => 'LAP-LOW-003',
                'name' => 'Laptop ASUS VivoBook Go 15 E1504 (Gama Económica)',
                'brand' => 'ASUS',
                'category_id' => $laptopCat->id,
                'condition' => 'nuevo',
                'cost_price' => 2550.00,
                'sale_price' => 3190.00,
                'stock' => 3,
                'min_stock' => 1,
                'warranty_days' => 365,
                'status' => 'active',
                'image_path' => '/images/placeholders/laptop.svg',
                'gallery_images' => [
                    '/images/placeholders/laptop.svg',
                    '/images/placeholders/laptop.svg',
                    '/images/placeholders/laptop.svg',
                ],
                'description' => '[GAMA BAJA] Procesador AMD Ryzen 3 7320U (4 núcleos / 8 hilos), 8GB Memoria LPDDR5, 256GB SSD PCIe, Pantalla 15.6" Full HD con bisagra plana de 180° y escudo de privacidad en cámara web. Muy eficiente en consumo de batería y fluida en tareas diarias.',
            ],

            // ==================== GAMA MEDIA / PRODUCTIVIDAD ====================
            [
                'sku' => 'LAP-MED-001',
                'name' => 'Laptop Lenovo IdeaPad Slim 3 15IRH8 (Gama Media Profesional)',
                'brand' => 'Lenovo',
                'category_id' => $laptopCat->id,
                'condition' => 'nuevo',
                'cost_price' => 4150.00,
                'sale_price' => 5150.00,
                'stock' => 4,
                'min_stock' => 1,
                'warranty_days' => 365,
                'status' => 'active',
                'image_path' => '/images/placeholders/laptop.svg',
                'gallery_images' => [
                    '/images/placeholders/laptop.svg',
                    '/images/placeholders/laptop.svg',
                    '/images/placeholders/laptop.svg',
                ],
                'description' => '[GAMA MEDIA ALTO RENDIMIENTO] Procesador Intel Core i5-13420H Serie H de alto desempeño (8 núcleos hasta 4.6GHz), 16GB RAM LPDDR5 4800MHz, 512GB SSD NVMe PCIe 4.0, Pantalla 15.6" IPS Full HD con bordes delgados, audio Dolby Audio, Wi-Fi 6 y carga rápida Rapid Charge Boost. Recomendada para programación, análisis de datos, multitarea pesada y diseño gráfico en Photoshop e Illustrator.',
            ],
            [
                'sku' => 'LAP-MED-002',
                'name' => 'Laptop Dell Inspiron 15 3525 (Gama Media Fluida 120Hz)',
                'brand' => 'Dell',
                'category_id' => $laptopCat->id,
                'condition' => 'nuevo',
                'cost_price' => 3850.00,
                'sale_price' => 4790.00,
                'stock' => 3,
                'min_stock' => 1,
                'warranty_days' => 365,
                'status' => 'active',
                'image_path' => '/images/placeholders/laptop.svg',
                'gallery_images' => [
                    '/images/placeholders/laptop.svg',
                    '/images/placeholders/laptop.svg',
                    '/images/placeholders/laptop.svg',
                ],
                'description' => '[GAMA MEDIA] Procesador AMD Ryzen 5 5500U (6 núcleos / 12 hilos), 16GB RAM DDR4 3200MHz, 512GB SSD M.2 NVMe, Pantalla 15.6" Full HD con tasa de refresco ultra fluida de 120Hz, Gráficos AMD Radeon, chasis con bisagra ergonómica de elevación para mejor ventilación.',
            ],
            [
                'sku' => 'LAP-MED-003',
                'name' => 'Laptop HP Pavilion 15-eg3053 (Gama Media Premium Core i7)',
                'brand' => 'HP',
                'category_id' => $laptopCat->id,
                'condition' => 'nuevo',
                'cost_price' => 5100.00,
                'sale_price' => 6350.00,
                'stock' => 2,
                'min_stock' => 1,
                'warranty_days' => 365,
                'status' => 'active',
                'image_path' => '/images/placeholders/laptop.svg',
                'gallery_images' => [
                    '/images/placeholders/laptop.svg',
                    '/images/placeholders/laptop.svg',
                    '/images/placeholders/laptop.svg',
                ],
                'description' => '[GAMA MEDIA-ALTA CORPORATIVA] Procesador Intel Core i7-1355U (10 núcleos / 12 hilos hasta 5.0GHz), 16GB RAM DDR4, 512GB SSD NVMe Gen4, Pantalla táctil 15.6" FHD IPS de gran brillo, sistema de sonido Bang & Olufsen (B&O), teclado retroiluminado y acabado metálico en aluminio.',
            ],

            // ==================== GAMA ALTA / GAMER / ARQUITECTURA ====================
            [
                'sku' => 'LAP-HIGH-001',
                'name' => 'Laptop Gamer ASUS ROG Strix G16 (Gama Alta RTX 4060)',
                'brand' => 'ASUS',
                'category_id' => $laptopCat->id,
                'condition' => 'nuevo',
                'cost_price' => 8800.00,
                'sale_price' => 10850.00,
                'stock' => 2,
                'min_stock' => 1,
                'warranty_days' => 365,
                'status' => 'active',
                'image_path' => '/images/placeholders/laptop.svg',
                'gallery_images' => [
                    '/images/placeholders/laptop.svg',
                    '/images/placeholders/laptop.svg',
                    '/images/placeholders/laptop.svg',
                ],
                'description' => '[GAMA ALTA GAMER Y RENDER] Procesador Intel Core i7-13650HX (14 núcleos / 20 hilos), 16GB RAM DDR5 4800MHz (expandible a 64GB), 1TB SSD NVMe M.2 Gen 4, Tarjeta Gráfica Dedicada NVIDIA GeForce RTX 4060 8GB GDDR6 (potencia máxima 140W TGP con MUX Switch y NVIDIA Advanced Optimus), Pantalla 16" FHD+ (1920x1200) 165Hz con 100% sRGB, refrigeración inteligente ROG con metal líquido Conductonaut Extreme y 3 ventiladores. Diseñada para juegos AAA en ultra, AutoCAD, Revit, Lumion, Blender y edición de video en 4K.',
            ],
            [
                'sku' => 'LAP-HIGH-002',
                'name' => 'Laptop Lenovo Legion Pro 5 16IRX8 (Gama Alta Extrema RTX 4070)',
                'brand' => 'Lenovo',
                'category_id' => $laptopCat->id,
                'condition' => 'nuevo',
                'cost_price' => 11200.00,
                'sale_price' => 13700.00,
                'stock' => 2,
                'min_stock' => 1,
                'warranty_days' => 365,
                'status' => 'active',
                'image_path' => '/images/placeholders/laptop.svg',
                'gallery_images' => [
                    '/images/placeholders/laptop.svg',
                    '/images/placeholders/laptop.svg',
                    '/images/placeholders/laptop.svg',
                ],
                'description' => '[GAMA ALTA PROFESIONAL EXTREMA] Procesador Intel Core i7-13700HX (16 núcleos / 24 hilos hasta 5.0GHz), 32GB RAM DDR5 5200MHz Dual Channel, 1TB SSD NVMe PCIe 4.0, Tarjeta Gráfica NVIDIA GeForce RTX 4070 8GB GDDR6 (140W TGP con chip IA Lenovo LA1), Pantalla 16" WQXGA 2.5K (2560x1600) a 240Hz, 500 nits, 100% sRGB, DisplayHDR 400 y G-Sync, Teclado Legion TrueStrike con RGB por tecla. Máxima potencia para ingenieros de software, renders 3D complejos en V-Ray, IA local y simulación estructural.',
            ],
            [
                'sku' => 'LAP-HIGH-003',
                'name' => 'Laptop Gamer HP OMEN 16 (Gama Alta Ryzen 7 + RTX 4060)',
                'brand' => 'HP',
                'category_id' => $laptopCat->id,
                'condition' => 'nuevo',
                'cost_price' => 8100.00,
                'sale_price' => 9980.00,
                'stock' => 3,
                'min_stock' => 1,
                'warranty_days' => 365,
                'status' => 'active',
                'image_path' => '/images/placeholders/laptop.svg',
                'gallery_images' => [
                    '/images/placeholders/laptop.svg',
                    '/images/placeholders/laptop.svg',
                    '/images/placeholders/laptop.svg',
                ],
                'description' => '[GAMA ALTA GAMER] Procesador AMD Ryzen 7 7840HS (8 núcleos / 16 hilos hasta 5.1GHz con arquitectura Zen 4 y acelerador de IA Ryzen AI), 16GB RAM DDR5 5600MHz, 1TB SSD NVMe Gen4, Tarjeta Gráfica NVIDIA GeForce RTX 4060 8GB GDDR6, Pantalla 16.1" Full HD IPS a 144Hz con micro-bordes y antirreflejo, audio Bang & Olufsen con DTS:X Ultra.',
            ],
            [
                'sku' => 'LAP-HIGH-004',
                'name' => 'Laptop Dell XPS 15 9530 (Gama Ultra Alta Creador OLED 3.5K)',
                'brand' => 'Dell',
                'category_id' => $laptopCat->id,
                'condition' => 'nuevo',
                'cost_price' => 13500.00,
                'sale_price' => 16500.00,
                'stock' => 1,
                'min_stock' => 1,
                'warranty_days' => 365,
                'status' => 'active',
                'image_path' => '/images/placeholders/laptop.svg',
                'gallery_images' => [
                    '/images/placeholders/laptop.svg',
                    '/images/placeholders/laptop.svg',
                    '/images/placeholders/laptop.svg',
                ],
                'description' => '[GAMA ULTRA ALTA PREMIUM EJECUTIVA] Procesador Intel Core i9-13900H (14 núcleos / 20 hilos hasta 5.4GHz), 32GB RAM DDR5 4800MHz, 1TB SSD NVMe PCIe 4.0, Tarjeta Gráfica NVIDIA GeForce RTX 4060 8GB GDDR6 con controladores Studio, Pantalla Táctil 15.6" OLED 3.5K (3456x2160) InfinityEdge, 100% DCI-P3, relación 16:10, chasis de aluminio mecanizado CNC y reposamanos de fibra de carbono aeroespacial. La laptop definitiva para directores de arte, cineastas y ejecutivos.',
            ],
        ];

        foreach ($laptops as $data) {
            $brandName = $data['brand'];
            unset($data['brand']);
            $data['brand_id'] = $brands[$brandName]->id;

            ProductModel::updateOrCreate(
                ['sku' => $data['sku']],
                $data
            );
        }
    }
}
