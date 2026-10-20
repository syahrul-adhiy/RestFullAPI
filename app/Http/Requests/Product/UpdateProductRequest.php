<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $productId = $this->route('product')?->id ?? $this->route('product');

        return [
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id,deleted_at,NULL'],
            'name' => ['sometimes', 'required', 'string', 'min:3', 'max:150'],
            'sku' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('products', 'sku')->ignore($productId),
            ],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:180',
                Rule::unique('products', 'slug')->ignore($productId),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'stock' => ['sometimes', 'required', 'integer', 'min:0'],
            'material' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', 'string', 'in:draft,published,archived'],
            'image' => [
                'nullable',
                'file',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:5120',
            ],
            'document' => [
                'nullable',
                'file',
                'mimes:pdf,doc,docx',
                'max:10240',
            ],
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
            'category_id.exists' => 'Kategori yang dipilih tidak valid atau sudah dihapus.',
            'name.min' => 'Nama produk minimal 3 karakter.',
            'sku.unique' => 'SKU produk sudah digunakan oleh produk lain.',
            'price.numeric' => 'Harga harus berupa angka valid.',
            'price.min' => 'Harga tidak boleh bernilai negatif.',
            'stock.integer' => 'Stok harus berupa bilangan bulat.',
            'stock.min' => 'Stok tidak boleh bernilai negatif.',
            'image.image' => 'Berkas gambar harus berupa file gambar valid.',
            'image.mimes' => 'Format gambar harus jpeg, png, jpg, atau webp.',
            'image.max' => 'Ukuran berkas gambar maksimal 5MB.',
        ];
    }
}
