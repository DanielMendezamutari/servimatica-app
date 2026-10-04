<?php

namespace App\Application\Company;

use App\Domain\Company\CompanySettingRepositoryInterface;

class GetPublicCompanyInfoUseCase
{
    public function __construct(
        private readonly CompanySettingRepositoryInterface $repository
    ) {
    }

    /**
     * Retorna los datos institucionales públicos y ligeros de la empresa.
     *
     * @return array<string, mixed>
     */
    public function execute(): array
    {
        $company = $this->repository->get();

        return [
            'trade_name' => $company->tradeName,
            'slogan' => $company->slogan,
            'branch_name' => $company->branchName,
            'city' => $company->city,
            'address' => $company->address,
            'mobile' => $company->mobile,
            'email' => $company->email,
            'logo_url' => $company->logoUrl,
            'receipt_footer_message' => $company->receiptFooterMessage,
            'warranty_terms' => $company->warrantyTerms,
        ];
    }
}
