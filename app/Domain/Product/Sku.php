<?php
namespace App\Domain\Product;
final readonly class Sku {
    public string $value;
    public function __construct(string $value) {
        $value=mb_strtoupper(trim($value));
        if ($value === '' || mb_strlen($value)>50) { throw new \InvalidArgumentException('El SKU es obligatorio y admite hasta 50 caracteres.'); }
        $this->value=$value;
    }
}
