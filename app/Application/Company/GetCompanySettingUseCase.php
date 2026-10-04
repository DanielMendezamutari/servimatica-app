<?php

namespace App\Application\Company;

use App\Domain\Company\CompanySetting;
use App\Domain\Company\CompanySettingRepositoryInterface;

final class GetCompanySettingUseCase
{
    public function __construct(
        private readonly CompanySettingRepositoryInterface $repository
    ) {}

    public function execute(): CompanySetting
    {
        return $this->repository->get();
    }
}
