<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\PaymentMethod\PaymentMethod;
use App\Domain\PaymentMethod\PaymentMethodScope;
use App\Domain\PaymentMethod\PaymentMethodType;
use Illuminate\Database\Eloquent\Model;

class PaymentMethodModel extends Model
{
    protected $table = 'payment_methods';

    protected $fillable = [
        'name',
        'type',
        'bank_name',
        'account_number',
        'account_holder',
        'qr_image_path',
        'requires_reference',
        'applies_to',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'requires_reference' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function toDomain(): PaymentMethod
    {
        return new PaymentMethod(
            id: $this->id,
            name: $this->name,
            type: PaymentMethodType::from($this->type),
            bankName: $this->bank_name,
            accountNumber: $this->account_number,
            accountHolder: $this->account_holder,
            qrImagePath: $this->qr_image_path,
            requiresReference: (bool) $this->requires_reference,
            appliesTo: PaymentMethodScope::from($this->applies_to),
            sortOrder: (int) $this->sort_order,
            isActive: (bool) $this->is_active,
            createdAt: $this->created_at?->toIso8601String(),
            updatedAt: $this->updated_at?->toIso8601String()
        );
    }

    public static function fromDomain(PaymentMethod $domain): self
    {
        $model = new self();
        if ($domain->getId()) {
            $model = self::find($domain->getId()) ?? $model;
        }

        $model->name = $domain->getName();
        $model->type = $domain->getType()->value;
        $model->bank_name = $domain->getBankName();
        $model->account_number = $domain->getAccountNumber();
        $model->account_holder = $domain->getAccountHolder();
        $model->qr_image_path = $domain->getQrImagePath();
        $model->requires_reference = $domain->getRequiresReference();
        $model->applies_to = $domain->getAppliesTo()->value;
        $model->sort_order = $domain->getSortOrder();
        $model->is_active = $domain->isActive();

        return $model;
    }
}
