<?php

namespace App\Application\User;

use App\Domain\User\{Email, Password, PinCode, Role, User, Username, UserRepositoryInterface, UserStatus};
use Illuminate\Validation\ValidationException;

final readonly class RegisterUserUseCase
{
    public function __construct(private UserRepositoryInterface $users)
    {
    }
    public function execute(array $data): User
    {
        foreach (['username', 'email'] as $field) {
            if ($this->users->identityExists($field, strtolower(trim($data[$field])))) {
                throw ValidationException::withMessages([$field => 'Este valor ya está registrado.']);
            }
        }
        return $this->users->save(new User(
            null,
            trim($data['name']),
            new Username($data['username']),
            new Email($data['email']),
            Password::fromPlainText($data['password']),
            PinCode::fromPlainText($data['pin']),
            Role::from($data['role']),
            UserStatus::Active
        ));
    }
}
