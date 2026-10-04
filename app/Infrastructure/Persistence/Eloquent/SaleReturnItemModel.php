<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleReturnItemModel extends Model
{
    protected $table = 'sale_return_items';

    protected $fillable = [
        'sale_return_id',
        'sale_item_id',
        'product_id',
        'product_name',
        'quantity',
        'unit_price',
        'subtotal',
        'condition',
        'serial_number',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturnModel::class, 'sale_return_id');
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItemModel::class, 'sale_item_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }
}
