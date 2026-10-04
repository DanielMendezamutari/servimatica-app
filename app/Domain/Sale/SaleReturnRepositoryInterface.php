<?php

namespace App\Domain\Sale;

interface SaleReturnRepositoryInterface
{
    public function findById(int $id): ?SaleReturn;

    public function findByReturnNumber(string $number): ?SaleReturn;

    public function getNextReturnNumber(): string;

    public function save(SaleReturn $saleReturn): SaleReturn;

    /**
     * @return array{data: list<array<string, mixed>>, total: int, per_page: int, current_page: int, last_page: int}
     */
    public function list(int $page = 1, int $perPage = 15, ?string $resolution = null, ?string $search = null): array;

    /**
     * Retorna la suma acumulada de cantidades devueltas por cada ítem de una venta: [sale_item_id => int]
     *
     * @return array<int, int>
     */
    public function getReturnedQuantitiesBySaleId(int $saleId): array;
}
