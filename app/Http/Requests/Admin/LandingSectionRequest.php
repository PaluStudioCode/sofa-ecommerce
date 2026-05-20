<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LandingSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_landing_content') === true;
    }

    public function rules(): array
    {
        return [
            'section_key' => ['required', 'string', 'max:100', Rule::in(['hero', 'value', 'promo', 'featured_products', 'shopping_flow'])],
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'button_label' => ['nullable', 'string', 'max:100'],
            'button_url' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
