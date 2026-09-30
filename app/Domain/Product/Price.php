<?php
namespace App\Domain\Product;
final readonly class Price {
    public string $value;
    public function __construct(string|int|float $value) {
        if (!is_numeric($value) || $value < 0 || $value > 99999999.99 || !preg_match('/\A\d+(?:\.\d{1,2})?\z/', (string)$value)) {
            throw new \InvalidArgumentException('El precio debe ser positivo o cero y tener como máximo dos decimales.');
        }
        $this->value=number_format((float)$value,2,'.','');
    }
}
