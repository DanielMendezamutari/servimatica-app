<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItemModel extends Model
{
    protected $table = 'purchase_items';
    public $timestamps = false;

    protected $fillable = [
        'purchase_id',
        'product_id',
        'product_name',
        'product_sku',
        'quantity',
        'unit_cost',
        'subtotal',
        'previous_cost',
        'previous_sale_price',
        'new_sale_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'previous_cost' => 'decimal:2',
        'previous_sale_price' => 'decimal:2',
        'new_sale_price' => 'decimal:2',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(PurchaseModel::class, 'purchase_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }
}
