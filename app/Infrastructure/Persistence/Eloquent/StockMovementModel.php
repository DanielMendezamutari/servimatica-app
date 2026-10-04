<?php
namespace App\Infrastructure\Persistence\Eloquent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class StockMovementModel extends Model {
    protected $table='stock_movements';
    const UPDATED_AT=null;
    protected $fillable=['product_id','user_id','type','quantity','previous_stock','new_stock','reason','unit_cost','total_cost','reference_type','reference_id'];
    protected function casts(): array {
        return [
            'quantity'=>'integer',
            'previous_stock'=>'integer',
            'new_stock'=>'integer',
            'unit_cost'=>'float',
            'total_cost'=>'float',
            'reference_id'=>'integer',
        ];
    }
    protected static function booted(): void {
        static::updating(fn () => throw new \LogicException('Los movimientos de stock son inmutables.'));
        static::deleting(fn () => throw new \LogicException('Los movimientos de stock son inmutables.'));
    }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function product(): BelongsTo { return $this->belongsTo(ProductModel::class, 'product_id'); }
}
