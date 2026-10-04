<?php

namespace App\Application\PaymentMethod;

use App\Domain\PaymentMethod\PaymentMethodRepositoryInterface;
use App\Domain\PaymentMethod\PaymentMethodScope;
use App\Domain\PaymentMethod\PaymentMethodType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class UpdatePaymentMethodUseCase
{
    public function __construct(
        private PaymentMethodRepositoryInterface $repository
    ) {}

    public function execute(int $id, array $data, ?UploadedFile $qrImage = null): array
    {
        $method = $this->repository->findById($id);
        if (!$method) {
            throw new InvalidArgumentException("Método de pago con ID {$id} no encontrado.");
        }

        $qrImagePath = $method->getQrImagePath();
        if ($qrImage !== null) {
            if ($qrImagePath && Storage::disk('public')->exists($qrImagePath)) {
                Storage::disk('public')->delete($qrImagePath);
            }
            $qrImagePath = Storage::disk('public')->putFile('payment-methods', $qrImage);
        }

        $type = PaymentMethodType::from($data['type'] ?? $method->getType()->value);
        $appliesTo = isset($data['applies_to']) 
            ? PaymentMethodScope::from($data['applies_to']) 
            : $method->getAppliesTo();

        $method->update(
            name: $data['name'] ?? $method->getName(),
            type: $type,
            bankName: array_key_exists('bank_name', $data) ? $data['bank_name'] : $method->getBankName(),
            accountNumber: array_key_exists('account_number', $data) ? $data['account_number'] : $method->getAccountNumber(),
            accountHolder: array_key_exists('account_holder', $data) ? $data['account_holder'] : $method->getAccountHolder(),
            qrImagePath: $qrImagePath,
            requiresReference: array_key_exists('requires_reference', $data) ? (bool) $data['requires_reference'] : $method->getRequiresReference(),
            appliesTo: $appliesTo,
            sortOrder: array_key_exists('sort_order', $data) ? (int) $data['sort_order'] : $method->getSortOrder()
        );

        $saved = $this->repository->save($method);

        return $saved->toArray();
    }
}
