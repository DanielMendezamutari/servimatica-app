<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseModel extends Model
{
    protected $table = 'purchases';

    protected $fillable = [
        'purchase_number',
        'invoice_number',
        'supplier_id',
        'user_id',
        'purchase_date',
        'payment_condition',
        'payment_method',
        'payment_method_id',
        'reference_number',
        'payment_status',
        'due_date',
        'subtotal',
        'total_amount',
        'status',
        'cancellation_reason',
        'cancelled_by',
        'cancelled_at',
        'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date:Y-m-d',
        'due_date' => 'date:Y-m-d',
        'subtotal' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'cancelled_at' => 'datetime',
    ];

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethodModel::class, 'payment_method_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(SupplierModel::class, 'supplier_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItemModel::class, 'purchase_id');
    }
}
