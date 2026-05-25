<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_vouchers') === true;
    }

    public function rules(): array
    {
        $voucherId = $this->route('voucher')?->id;

        return [
            'code' => ['required', 'string', 'max:100', Rule::unique('vouchers', 'code')->ignore($voucherId)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'discount_type' => ['required', Rule::in(['nominal', 'percentage'])],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'minimum_purchase' => ['required', 'numeric', 'min:0'],
            'quota' => ['nullable', 'integer', 'min:0'],
            'per_user_limit' => ['nullable', 'integer', 'min:0'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after_or_equal:start_at'],
            'status' => ['required', Rule::in(['aktif', 'nonaktif', 'kedaluwarsa', 'kuota_habis'])],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('discount_type') === 'percentage' && (float) $this->input('discount_value') > 100) {
                $validator->errors()->add('discount_value', 'Diskon persentase tidak boleh lebih dari 100%.');
            }

            $usedCount = (int) ($this->route('voucher')?->orders()->count() ?? 0);
            $quota = $this->input('quota');

            if ($quota !== null && $quota !== '' && (int) $quota < $usedCount) {
                $validator->errors()->add('quota', 'Kuota tidak boleh lebih kecil dari jumlah penggunaan saat ini.');
            }
        });
    }
}
