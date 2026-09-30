<?php
namespace App\Infrastructure\Persistence\Eloquent;
use App\Domain\StockMovement\{StockMovement,StockMovementRepositoryInterface};
use App\Domain\Product\StockQuantity;
use Illuminate\Support\Facades\DB;
final class EloquentStockMovementRepository implements StockMovementRepositoryInterface {
    public function adjust(int $productId,int $userId,array $data): array {
        return DB::transaction(function()use($productId,$userId,$data) {
            $product=ProductModel::lockForUpdate()->findOrFail($productId);
            $before=$product->stock; $after=(new StockQuantity($before))->adjust($data['type'],(int)$data['quantity'])->value;
            $product->update(['stock'=>$after]);
            $movement=StockMovementModel::create(['product_id'=>$productId,'user_id'=>$userId,
                'type'=>$data['type'],'quantity'=>$data['quantity'],'previous_stock'=>$before,'new_stock'=>$after,'reason'=>$data['reason']]);
            return ['productId'=>$productId,'productName'=>$product->name,'previousStock'=>$before,'newStock'=>$after,'movement'=>$this->map($movement->load('user'))->toArray()];
        },3);
    }
    public function paginate(int $productId,array $filters): array {
        $product=ProductModel::findOrFail($productId);
        $query=StockMovementModel::with('user')->where('product_id',$productId);
        if(!empty($filters['type'])) { $query->where('type',$filters['type']); }
        if(!empty($filters['from'])) { $query->where('created_at','>=',\Carbon\CarbonImmutable::parse($filters['from'],'America/La_Paz')->startOfDay()->utc()); }
        if(!empty($filters['to'])) { $query->where('created_at','<',\Carbon\CarbonImmutable::parse($filters['to'],'America/La_Paz')->addDay()->startOfDay()->utc()); }
        $page=$query->orderByDesc('created_at')->orderByDesc('id')->paginate($filters['per_page']??20,['*'],'page',$filters['page']??1);
        return ['data'=>$page->getCollection()->map(fn($m)=>$this->map($m)->toArray())->all(),
            'meta'=>['currentPage'=>$page->currentPage(),'lastPage'=>$page->lastPage(),'perPage'=>$page->perPage(),'total'=>$page->total()],
            'product'=>['id'=>$product->id,'name'=>$product->name,'sku'=>$product->sku,'currentStock'=>$product->stock]];
    }
    private function map(StockMovementModel $m): StockMovement {
        return new StockMovement($m->id,$m->type,$m->quantity,$m->previous_stock,$m->new_stock,$m->reason,$m->user_id,$m->user->name,$m->created_at->toISOString());
    }
}
