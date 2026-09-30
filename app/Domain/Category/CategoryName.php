<?php
namespace App\Domain\Category;
final readonly class CategoryName {
    public string $value;
    public function __construct(string $value) {
        $value=trim($value);
        if ($value === '' || mb_strlen($value)>100) { throw new \InvalidArgumentException('El nombre de categoría es obligatorio y admite hasta 100 caracteres.'); }
        $this->value=$value;
    }
}
