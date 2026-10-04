<?php

namespace App\Application\PaymentMethod;

use App\Domain\PaymentMethod\PaymentMethod;
use App\Domain\PaymentMethod\PaymentMethodRepositoryInterface;
use App\Domain\PaymentMethod\PaymentMethodScope;
use App\Domain\PaymentMethod\PaymentMethodType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CreatePaymentMethodUseCase
{
    public function __construct(
        private PaymentMethodRepositoryInterface $repository
    ) {}

    public function execute(array $data, ?UploadedFile $qrImage = null): array
    {
        $qrImagePath = null;
        if ($qrImage !== null) {
            $qrImagePath = Storage::disk('public')->putFile('payment-methods', $qrImage);
        }

        $type = PaymentMethodType::from($data['type']);
        $appliesTo = isset($data['applies_to']) 
            ? PaymentMethodScope::from($data['applies_to']) 
            : PaymentMethodScope::BOTH;

        $method = new PaymentMethod(
            id: null,
            name: $data['name'],
            type: $type,
            bankName: $data['bank_name'] ?? null,
            accountNumber: $data['account_number'] ?? null,
            accountHolder: $data['account_holder'] ?? null,
            qrImagePath: $qrImagePath,
            requiresReference: (bool) ($data['requires_reference'] ?? false),
            appliesTo: $appliesTo,
            sortOrder: (int) ($data['sort_order'] ?? 0),
            isActive: (bool) ($data['is_active'] ?? true)
        );

        $saved = $this->repository->save($method);

        return $saved->toArray();
    }
}
