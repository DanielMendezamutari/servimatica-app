<?php

namespace App\Application\User;

use App\Domain\User\UserRepositoryInterface;
use App\Infrastructure\Services\Excel\ExcelExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class ExportUsersToExcelUseCase
{
    public function __construct(
        private UserRepositoryInterface $users,
        private ExcelExportService $excel
    ) {}

    public function execute(): StreamedResponse
    {
        $allUsers = $this->users->all();

        $today = date('Y-m-d');
        $filename = "nomina_personal_servimatica_{$today}.xlsx";
        $sheetTitle = 'Nómina Personal';

        $headers = [
            'CI / Documento',
            'Nombre Completo',
            'Usuario (Alias)',
            'Correo Electrónico',
            'Rol',
            'Sucursal',
            'Teléfono',
            'Dirección',
            'Sexo',
            'Comisión (%)',
            'Estado',
            'Fecha Registro',
        ];

        $formats = [
            1 => 'text',
            2 => 'text',
            3 => 'text',
            4 => 'text',
            5 => 'text',
            6 => 'text',
            7 => 'text',
            8 => 'text',
            9 => 'text',
            10 => 'percentage',
            11 => 'text',
            12 => 'text',
        ];

        $rows = [];
        foreach ($allUsers as $u) {
            $genderLabel = match ($u->gender) {
                'masculino' => 'Masculino',
                'femenino' => 'Femenino',
                'otro' => 'Otro',
                default => 'No especificado',
            };

            $roleLabel = match ($u->role->value) {
                'dueno' => 'Dueño / Administrador',
                'vendedor' => 'Vendedor Mostrador',
                default => ucfirst($u->role->value),
            };

            $statusLabel = $u->status->value === 'active' ? 'Activo' : 'Inactivo';
            $regDate = $u->createdAt ? substr($u->createdAt, 0, 10) : '';

            $rows[] = [
                $u->ci ?: 'S/D',
                $u->name,
                $u->username->value,
                $u->email->value,
                $roleLabel,
                $u->branch ?: 'Casa Matriz',
                $u->phone ?: 'S/T',
                $u->address ?: 'S/D',
                $genderLabel,
                $u->salesCommission,
                $statusLabel,
                $regDate,
            ];
        }

        return $this->excel->download(
            $filename,
            $sheetTitle,
            $headers,
            $rows,
            $formats
        );
    }
}
