<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashShiftModel extends Model
{
    protected $table = 'cash_shifts';

    protected $fillable = [
        'user_id',
        'opening_amount',
        'closing_amount',
        'expected_amount',
        'difference',
        'total_cash_sales',
        'total_qr_sales',
        'status',
        'opened_at',
        'closed_at',
        'notes',
    ];

    protected $casts = [
        'opening_amount' => 'decimal:2',
        'closing_amount' => 'decimal:2',
        'expected_amount' => 'decimal:2',
        'difference' => 'decimal:2',
        'total_cash_sales' => 'decimal:2',
        'total_qr_sales' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(SaleModel::class, 'cash_shift_id');
    }
}
