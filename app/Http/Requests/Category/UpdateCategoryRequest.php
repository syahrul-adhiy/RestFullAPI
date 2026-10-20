<?php

namespace App\Http\Requests\Category;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $categoryId = $this->route('category')?->id ?? $this->route('category');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:120',
                Rule::unique('categories', 'slug')->ignore($categoryId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
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
            'name.required' => 'Nama kategori tidak boleh kosong.',
            'slug.unique' => 'Slug kategori sudah digunakan oleh kategori lain.',
        ];
    }
}
