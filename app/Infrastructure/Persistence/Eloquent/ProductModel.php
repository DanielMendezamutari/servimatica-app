<?php
namespace App\Infrastructure\Persistence\Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ProductModel extends Model {
    protected $table='products';
    protected $fillable=['name','description','sku','category_id','cost_price','sale_price','stock','min_stock','status'];
    protected $attributes=['status'=>'active', 'stock'=>0, 'min_stock'=>0];
    protected $hidden=['cost_price','min_stock'];
    protected function casts(): array { return ['cost_price'=>'decimal:2','sale_price'=>'decimal:2','stock'=>'integer','min_stock'=>'integer']; }
    public function category(): BelongsTo { return $this->belongsTo(CategoryModel::class,'category_id'); }
}
