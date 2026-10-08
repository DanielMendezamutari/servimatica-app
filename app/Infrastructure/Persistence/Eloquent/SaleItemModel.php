<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItemModel extends Model
{
    protected $table = 'sale_items';
    public $timestamps = false;

    protected $fillable = [
        'sale_id',
        'product_id',
        'product_name',
        'product_sku',
        'quantity',
        'unit_cost',
        'unit_price',
        'subtotal',
        'warranty_days',
        'warranty_expires_at',
        'warranty_hardware_days',
        'warranty_hardware_expires_at',
        'warranty_software_days',
        'warranty_software_expires_at',
        'serial_number',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'warranty_days' => 'integer',
        'warranty_expires_at' => 'date',
        'warranty_hardware_days' => 'integer',
        'warranty_hardware_expires_at' => 'date',
        'warranty_software_days' => 'integer',
        'warranty_software_expires_at' => 'date',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(SaleModel::class, 'sale_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }
}
