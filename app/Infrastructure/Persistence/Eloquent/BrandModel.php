<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BrandModel extends Model
{
    protected $table = 'brands';
    protected $fillable = ['name', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function models(): HasMany
    {
        return $this->hasMany(DeviceModel::class, 'brand_id');
    }
}
