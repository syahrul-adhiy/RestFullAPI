<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\BaseFormRequest;

class AdjustStockRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:in,out,adjustment'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Tipe mutasi stok (in, out, adjustment) wajib ditentukan.',
            'type.in' => 'Tipe mutasi hanya boleh: in (penambahan), out (pengurangan), atau adjustment (penyesuaian total).',
            'quantity.required' => 'Jumlah kuantitas wajib diisi.',
            'quantity.min' => 'Jumlah kuantitas minimal 1.',
        ];
    }
}
