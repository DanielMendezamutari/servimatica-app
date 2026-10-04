<?php

namespace App\Application\Client;

use App\Domain\Client\ClientRepositoryInterface;

final readonly class ExportClientsToCsvUseCase
{
    public function __construct(
        private ClientRepositoryInterface $repository
    ) {}

    public function execute(): string
    {
        $clients = $this->repository->getAllForExport();

        $handle = fopen('php://temp', 'r+');

        // BOM UTF-8 para apertura correcta en Microsoft Excel
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

        // Cabecera
        fputcsv($handle, [
            'ID',
            'Nombre / Razón Social',
            'Tipo de Cliente',
            'NIT / CI',
            'Teléfono / Celular',
            'Enlace WhatsApp',
            'Correo Electrónico',
            'Ciudad',
            'Dirección',
            'Notas Comerciales',
            'Estado',
            'Total Compras (Bs.)',
            'Cantidad de Ventas',
            'Última Compra',
            'Fecha de Registro',
        ]);

        foreach ($clients as $c) {
            fputcsv($handle, [
                $c['id'],
                $c['name'],
                $c['client_type_label'],
                $c['nit_ci'] ?? 'S/N',
                $c['phone'] ?? '',
                $c['whatsapp_url'] ?? '',
                $c['email'] ?? '',
                $c['city'] ?? '',
                $c['address'] ?? '',
                $c['notes'] ?? '',
                $c['is_active'] ? 'Activo' : 'Inactivo',
                number_format((float) ($c['total_spent_bs'] ?? 0), 2, '.', ''),
                $c['sales_count'] ?? 0,
                $c['last_purchase_at'] ?? 'Sin compras',
                $c['created_at'] ?? '',
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }
}
