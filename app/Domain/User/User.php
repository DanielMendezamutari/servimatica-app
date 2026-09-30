<?php

namespace App\Domain\User;

final readonly class User
{
    public function __construct(
        public ?int $id,
        public string $name,
        public Username $username,
        public Email $email,
        public Password $password,
        public PinCode $pinCode,
        public Role $role,
        public UserStatus $status,
        public ?string $createdAt = null,
    ) {
        if (trim($name) === '' || mb_strlen($name) > 150) {
            throw new \InvalidArgumentException('El nombre es obligatorio y admite hasta 150 caracteres.');
        }
    }
    public function publicData(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'username' => $this->username->value,
            'email' => $this->email->value, 'role' => $this->role->value, 'status' => $this->status->value,
            'createdAt' => $this->createdAt];
    }
    public function abilities(): array
    {
        return $this->role === Role::Owner
            ? [['action' => 'manage', 'subject' => 'all']]
            : [
                ['action' => 'read', 'subject' => 'Dashboard'],
                ['action' => 'read', 'subject' => 'Product'],
            ];
    }
}
