<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientModel extends Model
{
    protected $table = 'clients';

    protected $fillable = [
        'name',
        'nit_ci',
        'phone',
        'email',
        'address',
        'client_type',
        'city',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function sales(): HasMany
    {
        return $this->hasMany(SaleModel::class, 'client_id');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(QuoteModel::class, 'client_id');
    }
}
