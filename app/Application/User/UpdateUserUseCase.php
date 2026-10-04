<?php

namespace App\Application\User;

use App\Domain\User\{Email, Password, PinCode, Role, User, Username, UserRepositoryInterface};
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class UpdateUserUseCase
{
    public function __construct(private UserRepositoryInterface $users)
    {
    }
    public function execute(int $id, int $actorId, array $data): User
    {
        $existing = $this->users->find($id) ?? throw new NotFoundHttpException('Usuario no encontrado.');
        if ($id === $actorId && $data['role'] !== Role::Owner->value) {
            throw ValidationException::withMessages(['role' => 'No puede quitar el rol Dueño a su propia cuenta.']);
        }
        foreach (['username', 'email'] as $field) {
            if ($this->users->identityExists($field, strtolower(trim($data[$field])), $id)) {
                throw ValidationException::withMessages([$field => 'Este valor ya está registrado.']);
            }
        }
        return $this->users->save(new User(
            $id,
            trim($data['name']),
            new Username($data['username']),
            new Email($data['email']),
            !empty($data['password']) ? Password::fromPlainText($data['password']) : $existing->password,
            isset($data['pin']) && $data['pin'] !== '' ? PinCode::fromPlainText($data['pin']) : $existing->pinCode,
            Role::from($data['role']),
            $existing->status,
            $data['ci'] ?? $existing->ci,
            $data['phone'] ?? $existing->phone,
            $data['address'] ?? $existing->address,
            $data['gender'] ?? $existing->gender,
            isset($data['sales_commission']) ? (float) $data['sales_commission'] : $existing->salesCommission,
            $data['branch'] ?? $existing->branch,
            array_key_exists('avatar', $data) ? $data['avatar'] : $existing->avatar,
            null,
            $existing->createdAt
        ));
    }
}
