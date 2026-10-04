<?php

namespace App\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    protected function prepareForValidation(): void
    {
        if ($this->isMethod('PUT')) {
            \App\Models\User::findOrFail($this->route('id'));
        }
        foreach (['username', 'email'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => strtolower(trim($this->input($field)))]);
            }
        }
        if ($this->input('role') === 'administrador') {
            $this->merge(['role' => 'dueno']);
        }
        if ($this->isMethod('POST') && empty($this->input('email')) && !empty($this->input('username'))) {
            $this->merge(['email' => strtolower(trim($this->input('username'))) . '@servimatica.local']);
        }
        if (empty($this->input('pin')) && $this->isMethod('POST')) {
            $this->merge(['pin' => '0000']);
        }
    }
    public function rules(): array
    {
        $credentialRule = $this->isMethod('POST') ? 'required' : 'nullable';
        $id = $this->route('id');
        return [
            'name' => ['required', 'string', 'max:150'],
            'ci' => ['nullable', 'string', 'max:30', Rule::unique('users')->ignore($id)],
            'username' => ['required', 'string', 'regex:/\A[a-z0-9._-]{3,50}\z/', Rule::unique('users')->ignore($id)],
            'email' => ['nullable', 'string', 'email', 'max:150', Rule::unique('users')->ignore($id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', Rule::in(['masculino', 'femenino', 'otro'])],
            'sales_commission' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'branch' => ['nullable', 'string', 'max:100'],
            'avatar' => ['nullable'],
            'password' => [$credentialRule, 'string', 'min:6', function ($attribute, $value, $fail) {
                if ($value && strlen($value) > 72) {
                    $fail('La contraseña admite como máximo 72 bytes.');
                }
            }],
            'pin' => ['nullable', 'string', 'regex:/\A[0-9]{4}\z/'],
            'role' => ['required', Rule::in(['dueno', 'administrador', 'vendedor'])],
        ];
    }
    public function messages(): array
    {
        return [
            'required' => 'Este campo es obligatorio.',
            'string' => 'Debe ingresar un texto válido.',
            'max' => 'Este campo admite hasta :max caracteres.',
            'min' => 'Debe ingresar al menos :min caracteres.',
            'unique' => 'Este valor ya está registrado.',
            'email' => 'Ingrese un correo electrónico válido.',
            'username.regex' => 'El alias debe tener entre 3 y 50 letras o números sin espacios.',
            'pin.regex' => 'El PIN debe tener exactamente 4 números.',
            'role.in' => 'Seleccione Dueño o Vendedor.',
        ];
    }
}
