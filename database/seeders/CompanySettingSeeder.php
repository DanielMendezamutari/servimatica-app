<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanySettingSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('company_settings')->count() === 0) {
            DB::table('company_settings')->insert([
                'id' => 1,
                'trade_name' => 'Servimática PC',
                'legal_name' => 'Servimática Computación',
                'tax_id' => null,
                'slogan' => 'Venta de Equipos de Computación, Insumos y Servicio Técnico Especializado',
                'branch_name' => 'Sucursal Central',
                'city' => 'Beni — Bolivia',
                'address' => 'Calle Principal',
                'mobile' => '77000000',
                'phone' => null,
                'email' => 'contacto@servimatica.com',
                'logo_path' => null,
                'default_quote_terms' => "• Precios expresados en Bolivianos (Bs.), válidos hasta la fecha indicada.\n• Cotización sujeta a disponibilidad de inventario al momento de concretar la compra.\n• Todos nuestros equipos cuentan con garantía técnica oficial según políticas de Servimática.",
                'receipt_footer_message' => '¡Gracias por su preferencia! Conserve este comprobante para reclamos y validación de su garantía.',
                'warranty_terms' => "• La garantía técnica cubre exclusivamente fallas y defectos de fabricación por el plazo pactado en este comprobante.\n• La garantía queda automáticamente invalidada por daños físicos (golpes, caídas, quiñes), variaciones o sobrecargas eléctricas, humedad, contacto con líquidos, o si los sellos de seguridad han sido alterados o removidos.\n• Para hacer efectiva la garantía es requisito indispensable presentar este comprobante y el equipo con sus números de serie legibles.",
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('company_settings')->where('id', 1)->whereNull('warranty_terms')->update([
                'warranty_terms' => "• La garantía técnica cubre exclusivamente fallas y defectos de fabricación por el plazo pactado en este comprobante.\n• La garantía queda automáticamente invalidada por daños físicos (golpes, caídas, quiñes), variaciones o sobrecargas eléctricas, humedad, contacto con líquidos, o si los sellos de seguridad han sido alterados o removidos.\n• Para hacer efectiva la garantía es requisito indispensable presentar este comprobante y el equipo con sus números de serie legibles.",
            ]);
        }
    }
}
