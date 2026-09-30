<?php
namespace App\Domain\Product;
final readonly class StockQuantity {
    public function __construct(public int $value) {
        if ($value<0 || $value>4294967295) { throw new \InvalidArgumentException('El stock debe estar entre 0 y 4294967295 unidades.'); }
    }
    public function adjust(string $type,int $quantity): self {
        if (!in_array($type,['in','out'],true) || $quantity<=0) { throw new \InvalidArgumentException('Ingrese un tipo válido y una cantidad mayor a cero.'); }
        if ($type==='out' && $quantity>$this->value) { throw new \InvalidArgumentException("La cantidad solicitada ($quantity) supera el stock actual ($this->value)."); }
        return new self($this->value+($type==='in'?$quantity:-$quantity));
    }
}
