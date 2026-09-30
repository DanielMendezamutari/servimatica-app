<?php

namespace App\Domain\User;

enum UserStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
