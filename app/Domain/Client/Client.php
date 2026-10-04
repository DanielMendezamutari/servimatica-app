<?php

namespace App\Domain\Client;

final readonly class Client
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $nitCi = null,
        public ?string $phone = null,
        public ?string $email = null,
        public ?string $address = null,
        public string $clientType = 'final',
        public ?string $city = 'Trinidad',
        public ?string $notes = null,
        public bool $isActive = true,
        public ?string $createdAt = null,
        public ?string $updatedAt = null
    ) {}

    public function whatsappUrl(): ?string
    {
        if (! $this->phone) {
            return null;
        }

        // Limpiar caracteres no numéricos
        $clean = preg_replace('/\D/', '', $this->phone);
        if (strlen($clean) === 8 && in_array($clean[0], ['6', '7'])) {
            return "https://wa.me/591{$clean}";
        }

        if (strlen($clean) >= 10 && str_starts_with($clean, '591')) {
            return "https://wa.me/{$clean}";
        }

        return null;
    }

    public function clientTypeLabel(): string
    {
        return match ($this->clientType) {
            'mayorista' => 'Técnico / Mayorista',
            'empresa' => 'Empresa / Corporativo',
            default => 'Cliente Final',
        };
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'nit_ci' => $this->nitCi,
            'phone' => $this->phone,
            'whatsapp_url' => $this->whatsappUrl(),
            'email' => $this->email,
            'address' => $this->address,
            'client_type' => $this->clientType,
            'client_type_label' => $this->clientTypeLabel(),
            'city' => $this->city,
            'notes' => $this->notes,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
