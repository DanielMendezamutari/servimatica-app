<?php

namespace App\Domain\Company;

interface CompanySettingRepositoryInterface
{
    /**
     * Obtiene la configuración institucional activa.
     */
    public function get(): CompanySetting;

    /**
     * Actualiza o crea la configuración institucional activa.
     */
    public function save(array $data): CompanySetting;
}
