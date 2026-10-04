<?php

namespace App\Domain\Kardex;

interface KardexRepositoryInterface
{
    /**
     * Obtiene el reporte auditado de Kardex de un producto con saldos acumulados y CPP.
     *
     * @param int $productId
     * @param array $filters ['from' => string, 'to' => string, 'type' => string]
     * @return array
     */
    public function getProductKardex(int $productId, array $filters = []): array;
}
