<?php
namespace App\Domain\Product;
final readonly class Product {
    public function __construct(public int $id, public string $name, public ?string $description, public Sku $sku,
        public int $categoryId, public string $categoryName, public Price $costPrice, public Price $salePrice,
        public StockQuantity $stock, public StockQuantity $minStock, public string $status, public ?string $createdAt) {}
    public function toArray(bool $owner): array {
        $data=['id'=>$this->id,'name'=>$this->name,'description'=>$this->description,'sku'=>$this->sku->value,
            'categoryId'=>$this->categoryId,'categoryName'=>$this->categoryName,'salePrice'=>$this->salePrice->value,'stock'=>$this->stock->value];
        if ($owner) { $data += ['costPrice'=>$this->costPrice->value,'minStock'=>$this->minStock->value,'status'=>$this->status,'createdAt'=>$this->createdAt]; }
        return $data;
    }
}
