<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleModel extends Model
{
    protected $table = 'sales';

    protected $fillable = [
        'invoice_number',
        'quote_id',
        'seller_id',
        'client_id',
        'client_name',
        'client_nit_ci',
        'cash_shift_id',
        'payment_method',
        'payment_method_id',
        'reference_number',
        'subtotal',
        'discount_amount',
        'total_amount',
        'cash_tendered',
        'change_due',
        'commission_rate',
        'commission_amount',
        'status',
        'cancellation_reason',
        'cancelled_by',
        'cancelled_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'cash_tendered' => 'decimal:2',
        'change_due' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'cancelled_at' => 'datetime',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(ClientModel::class, 'client_id');
    }

    public function cashShift(): BelongsTo
    {
        return $this->belongsTo(CashShiftModel::class, 'cash_shift_id');
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(QuoteModel::class, 'quote_id');
    }

    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItemModel::class, 'sale_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethodModel::class, 'payment_method_id');
    }
}
