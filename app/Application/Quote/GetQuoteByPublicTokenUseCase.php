<?php

namespace App\Application\Quote;

use App\Domain\Quote\Quote;
use App\Domain\Quote\QuoteRepositoryInterface;

final readonly class GetQuoteByPublicTokenUseCase
{
    public function __construct(private QuoteRepositoryInterface $quotes)
    {
    }

    public function execute(string $token): ?Quote
    {
        return $this->quotes->findByPublicToken($token);
    }
}
