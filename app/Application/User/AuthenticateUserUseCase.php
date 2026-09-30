<?php

namespace App\Application\User;

use App\Domain\User\{User, UserRepositoryInterface, UserStatus};
use Symfony\Component\HttpKernel\Exception\HttpException;

final readonly class AuthenticateUserUseCase
{
    public function __construct(private UserRepositoryInterface $users)
    {
    }
    public function execute(string $login, #[\SensitiveParameter] ?string $password, #[\SensitiveParameter] ?string $pin): User
    {
        $user = $this->users->findByLogin($login);
        $valid = $user && ($pin !== null ? $user->pinCode->matches($pin) : $user->password->matches($password ?? ''));
        if (!$valid) {
            throw new HttpException(401, 'Credenciales no válidas. Verifique sus datos o su PIN.');
        }
        if ($user->status !== UserStatus::Active) {
            throw new HttpException(403, 'Su cuenta se encuentra inactiva. Consulte con administración.');
        }
        return $user;
    }
}
