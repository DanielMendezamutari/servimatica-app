<?php
namespace App\Infrastructure\Persistence\Eloquent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class StockMovementModel extends Model {
    protected $table='stock_movements';
    const UPDATED_AT=null;
    protected $fillable=['product_id','user_id','type','quantity','previous_stock','new_stock','reason'];
    protected function casts(): array { return ['quantity'=>'integer','previous_stock'=>'integer','new_stock'=>'integer']; }
    protected static function booted(): void {
        static::updating(fn () => throw new \LogicException('Los movimientos de stock son inmutables.'));
        static::deleting(fn () => throw new \LogicException('Los movimientos de stock son inmutables.'));
    }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
