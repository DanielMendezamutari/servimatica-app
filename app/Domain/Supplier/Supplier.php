<?php

namespace App\Domain\Supplier;

final readonly class Supplier
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $nit = null,
        public ?string $contactName = null,
        public ?string $phone = null,
        public ?string $email = null,
        public ?string $city = null,
        public ?string $address = null,
        public bool $isActive = true,
        public ?string $createdAt = null
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'nit' => $this->nit,
            'contact_name' => $this->contactName,
            'phone' => $this->phone,
            'email' => $this->email,
            'city' => $this->city,
            'address' => $this->address,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt,
        ];
    }
}
