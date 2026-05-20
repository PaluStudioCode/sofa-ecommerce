<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_product_variants') === true;
    }

    public function rules(): array
    {
        $variantId = $this->route('variant')?->id;

        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('product_variants', 'sku')->ignore($variantId)],
            'variant_name' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'string', 'max:255'],
            'material' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'reserved_stock' => ['required', 'integer', 'min:0', 'lte:stock'],
            'status' => ['required', Rule::in(['aktif', 'nonaktif', 'stok_habis'])],
        ];
    }
}
