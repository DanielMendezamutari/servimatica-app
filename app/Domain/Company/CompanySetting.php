<?php

namespace App\Domain\Company;

final class CompanySetting
{
    public function __construct(
        public readonly int $id,
        public readonly string $tradeName,
        public readonly ?string $legalName,
        public readonly ?string $taxId,
        public readonly ?string $slogan,
        public readonly string $branchName,
        public readonly string $city,
        public readonly string $address,
        public readonly string $mobile,
        public readonly ?string $phone,
        public readonly ?string $email,
        public readonly ?string $logoPath,
        public readonly ?string $defaultQuoteTerms,
        public readonly ?string $receiptFooterMessage,
        public readonly ?string $warrantyTerms = null,
        public readonly ?string $logoUrl = null,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'trade_name' => $this->tradeName,
            'legal_name' => $this->legalName,
            'tax_id' => $this->taxId,
            'slogan' => $this->slogan,
            'branch_name' => $this->branchName,
            'city' => $this->city,
            'address' => $this->address,
            'mobile' => $this->mobile,
            'phone' => $this->phone,
            'email' => $this->email,
            'logo_path' => $this->logoPath,
            'logo_url' => $this->logoUrl,
            'default_quote_terms' => $this->defaultQuoteTerms,
            'receipt_footer_message' => $this->receiptFooterMessage,
            'warranty_terms' => $this->warrantyTerms,
        ];
    }
}
