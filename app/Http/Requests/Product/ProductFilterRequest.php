<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\BaseFormRequest;

class ProductFilterRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'status' => ['nullable', 'string', 'in:draft,published,archived'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'in_stock' => ['nullable', 'in:true,false,1,0'],
            'sort_by' => ['nullable', 'string', 'in:id,name,price,stock,created_at,status'],
            'sort_order' => ['nullable', 'string', 'in:asc,desc,ASC,DESC'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
