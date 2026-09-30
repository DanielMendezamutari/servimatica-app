<?php
namespace App\Infrastructure\Persistence\Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class CategoryModel extends Model {
    protected $table='categories';
    protected $fillable=['name','description','status'];
    protected $attributes=['status'=>'active'];
    public function products(): HasMany { return $this->hasMany(ProductModel::class,'category_id'); }
}
