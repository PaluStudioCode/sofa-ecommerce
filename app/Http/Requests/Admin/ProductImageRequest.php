<?php

namespace App\Http\Requests\Admin;

use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;

class ProductImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_products') === true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'image' => [$this->isMethod('post') ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_primary' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->filled('product_variant_id') || ! $this->filled('product_id')) {
                return;
            }

            $belongsToProduct = ProductVariant::query()
                ->whereKey($this->integer('product_variant_id'))
                ->where('product_id', $this->integer('product_id'))
                ->exists();

            if (! $belongsToProduct) {
                $validator->errors()->add('product_variant_id', 'Varian harus berasal dari produk yang sama.');
            }
        });
    }
}
