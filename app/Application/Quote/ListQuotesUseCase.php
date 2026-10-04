<?php

namespace App\Application\Quote;

use App\Domain\Quote\QuoteRepositoryInterface;

final readonly class ListQuotesUseCase
{
    public function __construct(
        private QuoteRepositoryInterface $repository
    ) {}

    public function execute(int $page = 1, int $perPage = 15, ?string $search = null, ?int $sellerId = null): array
    {
        return $this->repository->paginate($page, $perPage, $search, $sellerId);
    }
}
