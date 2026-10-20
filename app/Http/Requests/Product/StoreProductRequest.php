<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\BaseFormRequest;

class StoreProductRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id,deleted_at,NULL'],
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'sku' => ['required', 'string', 'max:50', 'unique:products,sku'],
            'slug' => ['nullable', 'string', 'max:180', 'unique:products,slug'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'material' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', 'string', 'in:draft,published,archived'],
            'image' => [
                'nullable',
                'file',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:5120', // 5MB
            ],
            'document' => [
                'nullable',
                'file',
                'mimes:pdf,doc,docx',
                'max:10240', // 10MB
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
            'category_id.required' => 'Kategori produk wajib dipilih.',
            'category_id.exists' => 'Kategori yang dipilih tidak valid atau sudah dihapus.',
            'name.required' => 'Nama produk topi wajib diisi.',
            'name.min' => 'Nama produk minimal 3 karakter.',
            'sku.required' => 'SKU produk wajib diisi.',
            'sku.unique' => 'SKU produk sudah digunakan. Gunakan kode SKU yang unik.',
            'price.required' => 'Harga produk wajib diisi.',
            'price.numeric' => 'Harga harus berupa angka valid.',
            'price.min' => 'Harga tidak boleh bernilai negatif.',
            'stock.required' => 'Stok awal produk wajib diisi.',
            'stock.integer' => 'Stok harus berupa bilangan bulat.',
            'stock.min' => 'Stok tidak boleh bernilai negatif.',
            'status.in' => 'Status produk hanya boleh: draft, published, atau archived.',
            'image.image' => 'Berkas gambar harus berupa file gambar valid.',
            'image.mimes' => 'Format gambar harus jpeg, png, jpg, atau webp.',
            'image.max' => 'Ukuran berkas gambar maksimal 5MB.',
            'document.mimes' => 'Format dokumen harus pdf, doc, atau docx.',
            'document.max' => 'Ukuran berkas dokumen maksimal 10MB.',
        ];
    }
}
