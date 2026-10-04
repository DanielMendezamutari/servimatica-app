<?php

namespace App\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateCompanySettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'dueno';
    }

    public function rules(): array
    {
        return [
            'trade_name' => ['required', 'string', 'max:120'],
            'legal_name' => ['nullable', 'string', 'max:150'],
            'tax_id' => ['nullable', 'string', 'max:30'],
            'slogan' => ['nullable', 'string', 'max:200'],
            'branch_name' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:250'],
            'mobile' => ['required', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'default_quote_terms' => ['nullable', 'string'],
            'receipt_footer_message' => ['nullable', 'string'],
            'warranty_terms' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'trade_name.required' => 'El nombre comercial de la empresa es obligatorio.',
            'branch_name.required' => 'El nombre de la sucursal es obligatorio.',
            'city.required' => 'La ciudad y departamento son obligatorios.',
            'address.required' => 'La dirección física del establecimiento es obligatoria.',
            'mobile.required' => 'El número de celular o WhatsApp de contacto es obligatorio.',
            'logo.image' => 'El archivo de logotipo debe ser una imagen válida (.png, .jpg, .webp, .svg).',
            'logo.max' => 'El logotipo no debe superar los 2 MB de tamaño.',
        ];
    }
}
