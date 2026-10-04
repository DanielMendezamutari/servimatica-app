<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierModel extends Model
{
    protected $table = 'suppliers';

    protected $fillable = [
        'name',
        'nit',
        'contact_name',
        'phone',
        'email',
        'city',
        'address',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function purchases(): HasMany
    {
        return $this->hasMany(PurchaseModel::class, 'supplier_id');
    }
}
