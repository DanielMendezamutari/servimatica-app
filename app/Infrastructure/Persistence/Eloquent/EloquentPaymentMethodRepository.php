<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\PaymentMethod\PaymentMethod;
use App\Domain\PaymentMethod\PaymentMethodRepositoryInterface;

class EloquentPaymentMethodRepository implements PaymentMethodRepositoryInterface
{
    public function findAll(): array
    {
        return PaymentMethodModel::orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (PaymentMethodModel $m) => $m->toDomain())
            ->all();
    }

    public function findActive(?string $scope = null): array
    {
        $query = PaymentMethodModel::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id');

        if ($scope) {
            $query->where(function ($q) use ($scope) {
                $q->where('applies_to', $scope)
                  ->orWhere('applies_to', 'both');
            });
        }

        return $query->get()
            ->map(fn (PaymentMethodModel $m) => $m->toDomain())
            ->all();
    }

    public function findById(int $id): ?PaymentMethod
    {
        $model = PaymentMethodModel::find($id);

        return $model?->toDomain();
    }

    public function save(PaymentMethod $method): PaymentMethod
    {
        $model = PaymentMethodModel::fromDomain($method);
        $model->save();

        return $model->toDomain();
    }

    public function delete(int $id): bool
    {
        return (bool) PaymentMethodModel::destroy($id);
    }
}
