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
        public ?string $ci = null,
        public ?string $phone = null,
        public ?string $address = null,
        public ?string $gender = null,
        public float $salesCommission = 0.0,
        public string $branch = 'Casa Matriz',
        public ?string $avatar = null,
        public ?string $avatarUrl = null,
        public ?string $createdAt = null,
    ) {
        if (trim($name) === '' || mb_strlen($name) > 150) {
            throw new \InvalidArgumentException('El nombre es obligatorio y admite hasta 150 caracteres.');
        }
    }

    public function publicData(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'ci' => $this->ci,
            'username' => $this->username->value,
            'email' => $this->email->value,
            'phone' => $this->phone,
            'address' => $this->address,
            'gender' => $this->gender,
            'salesCommission' => $this->salesCommission,
            'sales_commission' => $this->salesCommission,
            'branch' => $this->branch,
            'avatar' => $this->avatar,
            'avatarUrl' => $this->avatarUrl,
            'avatar_url' => $this->avatarUrl,
            'role' => $this->role->value,
            'status' => $this->status->value,
            'createdAt' => $this->createdAt,
        ];
    }

    public function abilities(): array
    {
        return $this->role === Role::Owner
            ? [['action' => 'manage', 'subject' => 'all']]
            : [
                ['action' => 'read', 'subject' => 'Dashboard'],
                ['action' => 'read', 'subject' => 'Product'],
                ['action' => 'manage', 'subject' => 'Pos'],
                ['action' => 'manage', 'subject' => 'Quote'],
                ['action' => 'manage', 'subject' => 'CashShift'],
                ['action' => 'read', 'subject' => 'Sale'],
                ['action' => 'manage', 'subject' => 'Client'],
            ];
    }
}
