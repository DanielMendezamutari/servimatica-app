<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleReturnModel extends Model
{
    protected $table = 'sale_returns';

    protected $fillable = [
        'return_number',
        'sale_id',
        'client_id',
        'client_name',
        'user_id',
        'cash_shift_id',
        'resolution',
        'total_refund_amount',
        'reason',
        'status',
    ];

    protected $casts = [
        'total_refund_amount' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(SaleModel::class, 'sale_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(ClientModel::class, 'client_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cashShift(): BelongsTo
    {
        return $this->belongsTo(CashShiftModel::class, 'cash_shift_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleReturnItemModel::class, 'sale_return_id');
    }
}
