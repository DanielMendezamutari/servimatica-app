<?php

namespace App\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'categoryId' => ['required', 'integer', 'exists:categories,id'],
            'sku' => ['nullable', 'string', 'max:50'],
            'costPrice' => ['required', 'numeric', 'min:0'],
            'salePrice' => ['required', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'minStock' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El campo nombre es obligatorio.',
            'name.max' => 'El nombre admite hasta 200 caracteres.',
            'categoryId.required' => 'Debe seleccionar una categoría.',
            'categoryId.exists' => 'La categoría seleccionada no existe.',
            'costPrice.required' => 'El costo es obligatorio.',
            'costPrice.numeric' => 'El costo debe ser un valor numérico.',
            'costPrice.min' => 'El costo no puede ser negativo.',
            'salePrice.required' => 'El precio de venta es obligatorio.',
            'salePrice.numeric' => 'El precio de venta debe ser un valor numérico.',
            'salePrice.min' => 'El precio de venta no puede ser negativo.',
            'stock.integer' => 'El stock debe ser un número entero.',
            'stock.min' => 'El stock no puede ser negativo.',
            'minStock.integer' => 'El stock mínimo debe ser un número entero.',
            'minStock.min' => 'El stock mínimo no puede ser negativo.',
        ];
    }
}
