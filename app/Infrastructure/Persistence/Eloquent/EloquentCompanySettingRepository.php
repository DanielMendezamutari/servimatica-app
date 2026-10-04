<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Company\CompanySetting;
use App\Domain\Company\CompanySettingRepositoryInterface;

final class EloquentCompanySettingRepository implements CompanySettingRepositoryInterface
{
    public function get(): CompanySetting
    {
        $model = CompanySettingModel::query()->first();

        if (!$model) {
            return new CompanySetting(
                id: 1,
                tradeName: 'Servimática PC',
                legalName: 'Servimática Computación',
                taxId: null,
                slogan: 'Venta de Equipos de Computación, Insumos y Servicio Técnico Especializado',
                branchName: 'Sucursal Central',
                city: 'Beni — Bolivia',
                address: 'Calle Principal',
                mobile: '77000000',
                phone: null,
                email: 'contacto@servimatica.com',
                logoPath: null,
                defaultQuoteTerms: "• Precios expresados en Bolivianos (Bs.), válidos hasta la fecha indicada.\n• Cotización sujeta a disponibilidad de inventario al momento de concretar la compra.\n• Todos nuestros equipos cuentan con garantía técnica oficial según políticas de Servimática.",
                receiptFooterMessage: '¡Gracias por su preferencia! Conserve este comprobante para reclamos y validación de su garantía.',
                warrantyTerms: "• La garantía técnica cubre exclusivamente fallas y defectos de fabricación por el plazo pactado en este comprobante.\n• La garantía queda automáticamente invalidada por daños físicos (golpes, caídas, quiñes), variaciones o sobrecargas eléctricas, humedad, contacto con líquidos, o si los sellos de seguridad han sido alterados o removidos.\n• Para hacer efectiva la garantía es requisito indispensable presentar este comprobante y el equipo con sus números de serie legibles.",
                logoUrl: asset('images/logo_servimatica.png')
            );
        }

        return $this->toDomain($model);
    }

    public function save(array $data): CompanySetting
    {
        $model = CompanySettingModel::query()->first();

        if (!$model) {
            $data['id'] = 1;
            $model = CompanySettingModel::query()->create($data);
        } else {
            $model->update($data);
        }

        return $this->toDomain($model->fresh());
    }

    private function toDomain(CompanySettingModel $model): CompanySetting
    {
        $logoUrl = $model->logo_path
            ? asset('storage/' . $model->logo_path)
            : asset('images/logo_servimatica.png');

        return new CompanySetting(
            id: (int) $model->id,
            tradeName: (string) $model->trade_name,
            legalName: $model->legal_name ? (string) $model->legal_name : null,
            taxId: $model->tax_id ? (string) $model->tax_id : null,
            slogan: $model->slogan ? (string) $model->slogan : null,
            branchName: (string) $model->branch_name,
            city: (string) $model->city,
            address: (string) $model->address,
            mobile: (string) $model->mobile,
            phone: $model->phone ? (string) $model->phone : null,
            email: $model->email ? (string) $model->email : null,
            logoPath: $model->logo_path ? (string) $model->logo_path : null,
            defaultQuoteTerms: $model->default_quote_terms ? (string) $model->default_quote_terms : null,
            receiptFooterMessage: $model->receipt_footer_message ? (string) $model->receipt_footer_message : null,
            warrantyTerms: $model->warranty_terms ? (string) $model->warranty_terms : null,
            logoUrl: $logoUrl
        );
    }
}
