<?php

namespace App\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $hwDays = $this->input('warrantyHardwareDays') ?? $this->input('warranty_hardware_days') ?? $this->input('warrantyDays') ?? $this->input('warranty_days', 0);
        $swDays = $this->input('warrantySoftwareDays') ?? $this->input('warranty_software_days', 0);

        $this->merge([
            'categoryId' => $this->input('categoryId') ?? $this->input('category_id'),
            'subfamilyId' => $this->input('subfamilyId') ?? $this->input('subfamily_id'),
            'brandId' => $this->input('brandId') ?? $this->input('brand_id'),
            'productModelId' => $this->input('productModelId') ?? $this->input('product_model_id'),
            'costPrice' => $this->input('costPrice') ?? $this->input('cost_price'),
            'salePrice' => $this->input('salePrice') ?? $this->input('sale_price'),
            'minStock' => $this->input('minStock') ?? $this->input('min_stock', 0),
            'condition' => $this->input('condition') ?: 'nuevo',
            'warrantyDays' => $hwDays,
            'warrantyHardwareDays' => $hwDays,
            'warranty_hardware_days' => $hwDays,
            'warrantySoftwareDays' => $swDays,
            'warranty_software_days' => $swDays,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'categoryId' => ['required', 'integer', 'exists:categories,id'],
            'subfamilyId' => ['nullable', 'integer', 'exists:categories,id'],
            'brandId' => ['nullable', 'integer', 'exists:brands,id'],
            'productModelId' => ['nullable', 'integer', 'exists:product_models,id'],
            'condition' => ['required', 'string', 'in:nuevo,open_box,usado,reacondicionado'],
            'sku' => ['nullable', 'string', 'max:50'],
            'costPrice' => ['required', 'numeric', 'min:0'],
            'salePrice' => ['required', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'minStock' => ['nullable', 'integer', 'min:0'],
            'warrantyDays' => ['nullable', 'integer', 'min:0'],
            'warranty_days' => ['nullable', 'integer', 'min:0'],
            'warrantyHardwareDays' => ['nullable', 'integer', 'min:0'],
            'warranty_hardware_days' => ['nullable', 'integer', 'min:0'],
            'warrantySoftwareDays' => ['nullable', 'integer', 'min:0'],
            'warranty_software_days' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'gallery' => ['nullable', 'array', 'max:8'],
            'gallery.*' => ['file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'remove_image' => ['nullable', 'boolean'],
            'remove_gallery' => ['nullable', 'boolean'],
        ];
    }


    public function messages(): array
    {
        return [
            'name.required' => 'El campo nombre es obligatorio.',
            'name.max' => 'El nombre admite hasta 200 caracteres.',
            'categoryId.required' => 'Debe seleccionar una categoría.',
            'categoryId.exists' => 'La categoría seleccionada no existe.',
            'condition.in' => 'La condición debe ser: nuevo, open_box, usado o reacondicionado.',
            'costPrice.required' => 'El costo es obligatorio.',
            'costPrice.numeric' => 'El costo debe ser un valor numérico.',
            'costPrice.min' => 'El costo no puede ser negativo.',
            'salePrice.required' => 'El precio de venta es obligatorio.',
            'salePrice.numeric' => 'El precio de venta debe ser un valor numérico.',
            'salePrice.min' => 'El precio de venta no puede ser negativo.',
            'stock.integer' => 'El stock debe ser un número entero.',
            'warrantyDays.integer' => 'El tiempo de garantía debe ser un número entero de días.',
            'warrantyDays.min' => 'El tiempo de garantía no puede ser negativo.',
            'stock.min' => 'El stock no puede ser negativo.',
            'minStock.integer' => 'El stock mínimo debe ser un número entero.',
            'minStock.min' => 'El stock mínimo no puede ser negativo.',
        ];
    }
}
