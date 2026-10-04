<?php

namespace App\Application\Quote;

use App\Domain\Quote\Quote;
use App\Domain\Quote\QuoteRepositoryInterface;

final readonly class GetQuoteUseCase
{
    public function __construct(
        private QuoteRepositoryInterface $repository
    ) {}

    public function execute(int $id): ?Quote
    {
        return $this->repository->findById($id);
    }
}
