<?php

namespace App\Domain\Quote;

interface QuoteRepositoryInterface
{
    public function findById(int $id): ?Quote;
    public function findByQuoteNumber(string $quoteNumber): ?Quote;
    public function findByPublicToken(string $token): ?Quote;
    public function save(array $quoteData, array $itemsData): Quote;
    public function updateStatus(int $id, string $status): void;
    public function paginate(int $page = 1, int $perPage = 15, ?string $search = null, ?int $sellerId = null): array;
    public function getNextCorrelativeNumber(): string;
}

