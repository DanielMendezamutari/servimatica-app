<?php

namespace App\Domain\User;

enum Role: string
{
    case Owner = 'dueno';
    case Seller = 'vendedor';
}
