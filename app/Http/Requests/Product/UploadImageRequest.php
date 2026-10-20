<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\BaseFormRequest;

class UploadImageRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'image' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:5120', // 5MB
            ],
        ];
    }

    /**
     * Custom messages for validation.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.required' => 'Berkas gambar wajib diunggah.',
            'image.image' => 'Berkas harus berupa gambar valid.',
            'image.mimes' => 'Format gambar yang diperbolehkan adalah jpeg, png, jpg, atau webp.',
            'image.max' => 'Ukuran gambar tidak boleh melebihi 5MB.',
        ];
    }
}
