<?php

namespace App\Application\Purchase;

use App\Domain\Purchase\Purchase;
use App\Domain\Purchase\PurchaseRepositoryInterface;
use Illuminate\Validation\ValidationException;

final readonly class GetPurchaseUseCase
{
    public function __construct(
        private PurchaseRepositoryInterface $purchaseRepository
    ) {}

    public function execute(int $id): Purchase
    {
        $purchase = $this->purchaseRepository->findById($id);

        if (!$purchase) {
            throw ValidationException::withMessages(['purchase' => 'La orden de compra no fue encontrada.']);
        }

        return $purchase;
    }
}
