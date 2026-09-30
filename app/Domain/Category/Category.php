<?php
namespace App\Domain\Category;
final readonly class Category {
    public function __construct(public int $id, public CategoryName $name, public ?string $description,
        public string $status, public int $productsCount=0, public ?string $createdAt=null) {}
    public function toArray(): array {
        return ['id'=>$this->id,'name'=>$this->name->value,'description'=>$this->description,'status'=>$this->status,
            'productsCount'=>$this->productsCount,'createdAt'=>$this->createdAt];
    }
}
