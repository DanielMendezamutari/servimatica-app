<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuoteModel extends Model
{
    protected $table = 'quotes';

    protected $fillable = [
        'quote_number',
        'seller_id',
        'client_id',
        'client_name',
        'client_phone',
        'subtotal',
        'discount_amount',
        'total_amount',
        'valid_until',
        'status',
        'notes',
        'public_token',
    ];

    protected static function booted(): void
    {
        static::creating(function ($quote) {
            if (empty($quote->public_token)) {
                $quote->public_token = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    protected $casts = [

        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'valid_until' => 'date',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(ClientModel::class, 'client_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItemModel::class, 'quote_id');
    }
}
