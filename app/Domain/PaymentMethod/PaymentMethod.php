<?php

namespace App\Domain\PaymentMethod;

class PaymentMethod
{
    public function __construct(
        private ?int $id,
        private string $name,
        private PaymentMethodType $type,
        private ?string $bankName = null,
        private ?string $accountNumber = null,
        private ?string $accountHolder = null,
        private ?string $qrImagePath = null,
        private bool $requiresReference = false,
        private PaymentMethodScope $appliesTo = PaymentMethodScope::BOTH,
        private int $sortOrder = 0,
        private bool $isActive = true,
        private ?string $createdAt = null,
        private ?string $updatedAt = null
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): PaymentMethodType
    {
        return $this->type;
    }

    public function getBankName(): ?string
    {
        return $this->bankName;
    }

    public function getAccountNumber(): ?string
    {
        return $this->accountNumber;
    }

    public function getAccountHolder(): ?string
    {
        return $this->accountHolder;
    }

    public function getQrImagePath(): ?string
    {
        return $this->qrImagePath;
    }

    public function getRequiresReference(): bool
    {
        return $this->requiresReference;
    }

    public function getAppliesTo(): PaymentMethodScope
    {
        return $this->appliesTo;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isCash(): bool
    {
        return $this->type === PaymentMethodType::CASH;
    }

    public function isDigital(): bool
    {
        return in_array($this->type, [
            PaymentMethodType::QR,
            PaymentMethodType::BANK_TRANSFER,
            PaymentMethodType::CARD,
            PaymentMethodType::OTHER,
        ], true);
    }

    public function toggleStatus(): void
    {
        $this->isActive = !$this->isActive;
    }

    public function update(
        string $name,
        PaymentMethodType $type,
        ?string $bankName,
        ?string $accountNumber,
        ?string $accountHolder,
        ?string $qrImagePath,
        bool $requiresReference,
        PaymentMethodScope $appliesTo,
        int $sortOrder
    ): void {
        $this->name = $name;
        $this->type = $type;
        $this->bankName = $bankName;
        $this->accountNumber = $accountNumber;
        $this->accountHolder = $accountHolder;
        if ($qrImagePath !== null) {
            $this->qrImagePath = $qrImagePath;
        }
        $this->requiresReference = $requiresReference;
        $this->appliesTo = $appliesTo;
        $this->sortOrder = $sortOrder;
    }

    public function toArray(): array
    {
        $qrUrl = $this->qrImagePath ? asset('storage/' . $this->qrImagePath) : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'bank_name' => $this->bankName,
            'bankName' => $this->bankName,
            'account_number' => $this->accountNumber,
            'accountNumber' => $this->accountNumber,
            'account_holder' => $this->accountHolder,
            'accountHolder' => $this->accountHolder,
            'qr_image_path' => $this->qrImagePath,
            'qr_image_url' => $qrUrl,
            'qrImageUrl' => $qrUrl,
            'requires_reference' => $this->requiresReference,
            'requiresReference' => $this->requiresReference,
            'applies_to' => $this->appliesTo->value,
            'appliesTo' => $this->appliesTo->value,
            'applies_to_label' => $this->appliesTo->label(),
            'sort_order' => $this->sortOrder,
            'sortOrder' => $this->sortOrder,
            'is_active' => $this->isActive,
            'isActive' => $this->isActive,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
