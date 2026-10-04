<?php

namespace App\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateSaleReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'resolution' => ['required', 'string', 'in:cambio_fisico,reembolso_efectivo,nota_credito'],
            'reason' => ['required', 'string', 'max:500'],
            'cash_shift_id' => ['nullable', 'required_if:resolution,reembolso_efectivo', 'integer', 'exists:cash_shifts,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sale_item_id' => ['required', 'integer', 'exists:sale_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.condition' => ['required', 'string', 'in:stock_operativo,stock_defectuoso_rma'],
            'items.*.serial_number' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'resolution.required' => 'La resolución comercial es obligatoria.',
            'resolution.in' => 'La resolución seleccionada no es válida.',
            'reason.required' => 'El motivo o diagnóstico técnico de la devolución es obligatorio.',
            'cash_shift_id.required_if' => 'Debe especificar el turno de caja para procesar el egreso en efectivo.',
            'items.required' => 'Debe seleccionar al menos un producto para la devolución.',
            'items.min' => 'Debe seleccionar al menos un producto para la devolución.',
            'items.*.quantity.min' => 'La cantidad a devolver debe ser de al menos 1 unidad.',
            'items.*.condition.in' => 'La condición física del producto devuelto no es válida.',
        ];
    }
}
