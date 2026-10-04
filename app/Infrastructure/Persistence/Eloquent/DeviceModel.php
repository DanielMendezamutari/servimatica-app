<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceModel extends Model
{
    protected $table = 'product_models';
    protected $fillable = ['brand_id', 'name', 'notes', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(BrandModel::class, 'brand_id');
    }
}
