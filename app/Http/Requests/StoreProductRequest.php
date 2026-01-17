<?php

use App\Models\Category;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],

            'condition' => [
                Rule::requiredIf(function () {
                    $category = Category::find($this->category_id);
                    return $category?->supportsCondition();
                }),
                Rule::in(['baru', 'seken']),
                'nullable',
            ],
        ];
    }
}
