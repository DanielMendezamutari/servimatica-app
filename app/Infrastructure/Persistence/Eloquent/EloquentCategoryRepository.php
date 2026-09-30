<?php
namespace App\Infrastructure\Persistence\Eloquent;
use App\Domain\Category\{Category,CategoryName,CategoryRepositoryInterface};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
final class EloquentCategoryRepository implements CategoryRepositoryInterface {
    public function all(string $search=''): array {
        return CategoryModel::withCount('products')->when($search!=='',fn($q)=>$q->where('name','like','%'.$search.'%'))->orderBy('name')->get()->map(fn($m)=>$this->map($m))->all();
    }
    public function options(bool $owner): array {
        return CategoryModel::when(!$owner,fn($q)=>$q->where('status','active'))->orderBy('name')->get(['id','name'])->toArray();
    }
    private function name(array $data,?int $id=null): string {
        $name=(new CategoryName($data['name']))->value;
        if (CategoryModel::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->when($id,fn($q)=>$q->where('id','!=',$id))->exists()) {
            throw ValidationException::withMessages(['name'=>'El nombre de categoría ya está en uso.']);
        }
        return $name;
    }
    public function create(array $data): Category {
        $data['name']=$this->name($data);
        return $this->map(CategoryModel::create($data)->loadCount('products'));
    }
    public function update(int $id,array $data): Category {
        $model=CategoryModel::findOrFail($id); $data['name']=$this->name($data,$id);
        $model->update($data); return $this->map($model->loadCount('products'));
    }
    public function toggle(int $id): Category {
        return DB::transaction(function()use($id) {
            $m=CategoryModel::lockForUpdate()->findOrFail($id);
            $m->update(['status'=>$m->status==='active'?'inactive':'active']);
            return $this->map($m->loadCount('products'));
        });
    }
    public function delete(int $id): void {
        DB::transaction(function()use($id) {
            $m=CategoryModel::lockForUpdate()->findOrFail($id); $count=$m->products()->count();
            if ($count) { throw ValidationException::withMessages(['category'=> "No se puede eliminar la categoría porque tiene $count productos asignados. Reasígnelos primero."]); }
            $m->delete();
        });
    }
    private function map(CategoryModel $m): Category { return new Category($m->id,new CategoryName($m->name),$m->description,$m->status,(int)$m->products_count,$m->created_at?->toISOString()); }
}
