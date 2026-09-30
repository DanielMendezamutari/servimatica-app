<?php
namespace App\Infrastructure\Persistence\Eloquent;
use App\Domain\Product\{Product,Price,Sku,StockQuantity,ProductRepositoryInterface};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
final class EloquentProductRepository implements ProductRepositoryInterface {
    public function paginate(array $filters,bool $owner): array {
        $query=ProductModel::with('category');
        if (!$owner) { $query->where('status','active')->whereHas('category',fn($q)=>$q->where('status','active')); }
        elseif (!empty($filters['status'])) { $query->where('status',$filters['status']); }
        if (!empty($filters['search'])) { $term='%'.$filters['search'].'%'; $query->where(fn($q)=>$q->where('name','like',$term)->orWhere('sku','like',$term)); }
        if (!empty($filters['category_id'])) { $query->where('category_id',$filters['category_id']); }
        $page=$query->orderBy('name')->orderBy('id')->paginate($filters['per_page']??15,['*'],'page',$filters['page']??1);
        return ['data'=>$page->getCollection()->map(fn($m)=>$this->map($m)->toArray($owner))->all(),
            'meta'=>['currentPage'=>$page->currentPage(),'lastPage'=>$page->lastPage(),'perPage'=>$page->perPage(),'total'=>$page->total()]];
    }
    private function attributes(array $data): array {
        $mapped=['name'=>trim($data['name']),'description'=>$data['description']??null,'category_id'=>$data['categoryId'],
            'cost_price'=>(new Price($data['costPrice']))->value,'sale_price'=>(new Price($data['salePrice']))->value,
            'min_stock'=>(new StockQuantity((int)($data['minStock']??0)))->value];
        if (isset($data['status'])) { $mapped['status']=$data['status']; }
        return $mapped;
    }
    private function ensureSku(string $sku,?int $id=null): string {
        $sku=(new Sku($sku))->value;
        if(ProductModel::whereRaw('LOWER(sku) = ?', [mb_strtolower($sku)])->when($id,fn($q)=>$q->where('id','!=',$id))->exists()) {
            throw ValidationException::withMessages(['sku'=>'El código SKU ya está en uso por otro producto.']);
        }
        return $sku;
    }
    public function create(array $data): Product {
        return DB::transaction(function()use($data) {
            // Orden único de bloqueo para serializar altas, incluso entre categorías con igual prefijo.
            $categories=CategoryModel::orderBy('id')->lockForUpdate()->get();
            $category=$categories->firstWhere('id',$data['categoryId']);
            abort_unless($category,422,'La categoría seleccionada no existe.');
            $sku=trim($data['sku']??'');
            if($sku==='') {
                $prefix=strtoupper(substr(preg_replace('/[^A-Za-z]/','',Str::ascii($category->name)),0,3));
                $prefix=str_pad($prefix,3,'X');
                $max=0;
                foreach(ProductModel::where('category_id',$category->id)->pluck('sku') as $existing) {
                    if(preg_match('/^'.preg_quote($prefix,'/').'-(\d+)$/',$existing,$match)) { $max=max($max,(int)$match[1]); }
                }
                do { $sku=$prefix.'-'.str_pad((string)++$max,4,'0',STR_PAD_LEFT); } while(ProductModel::where('sku',$sku)->exists());
            }
            $m=ProductModel::create($this->attributes($data)+['sku'=>$this->ensureSku($sku),'stock'=>(new StockQuantity((int)($data['stock']??0)))->value]);
            return $this->map($m->load('category'));
        },3);
    }
    public function update(int $id,array $data): Product {
        return DB::transaction(function()use($id,$data) {
            CategoryModel::whereKey($data['categoryId'])->lockForUpdate()->firstOrFail();
            $m=ProductModel::lockForUpdate()->findOrFail($id);
            $attrs=$this->attributes($data);
            if(!empty($data['sku'])) { $attrs['sku']=$this->ensureSku($data['sku'],$id); }
            $m->update($attrs); // Nunca incluir stock: solo puede cambiar mediante un movimiento.
            return $this->map($m->load('category'));
        },3);
    }
    public function toggle(int $id): Product {
        return DB::transaction(function()use($id) {
            $m=ProductModel::lockForUpdate()->findOrFail($id);
            $m->update(['status'=>$m->status==='active'?'inactive':'active']);
            return $this->map($m->load('category'));
        });
    }
    private function map(ProductModel $m): Product {
        return new Product($m->id,$m->name,$m->description,new Sku($m->sku),$m->category_id,$m->category->name,
            new Price($m->cost_price),new Price($m->sale_price),new StockQuantity($m->stock),
            new StockQuantity($m->min_stock),$m->status,$m->created_at?->toISOString());
    }
}
