<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [

        'category_id' => 'required|exists:categories,id',

        'sku' => [
            'required',
            'max:100',
            Rule::unique('products')->ignore($this->product),
        ],

        'name' => 'required|max:255',

        'description' => 'nullable',

        'selling_price' => 'required|numeric|min:0',

        'minimum_stock' => 'required|integer|min:0',

        'is_active' => 'nullable|boolean',
    ];
    }
}
