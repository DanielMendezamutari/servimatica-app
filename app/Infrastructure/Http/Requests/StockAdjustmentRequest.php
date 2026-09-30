<?php

namespace App\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:in,out'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'El tipo de movimiento es obligatorio.',
            'type.in' => 'El tipo de movimiento debe ser ingreso (in) o egreso (out).',
            'quantity.required' => 'La cantidad es obligatoria.',
            'quantity.integer' => 'La cantidad debe ser un número entero.',
            'quantity.min' => 'La cantidad a ajustar debe ser mayor a cero.',
            'reason.required' => 'El motivo del ajuste es obligatorio.',
            'reason.max' => 'El motivo admite hasta 255 caracteres.',
        ];
    }
}
