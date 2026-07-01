<?php

namespace App\Http\Requests;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'affiliate_id' => ['nullable', 'integer', 'exists:affiliates,id'],
            'status' => ['nullable', Rule::in(OrderStatus::values())],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'min_value' => ['nullable', 'numeric', 'min:0'],
            'max_value' => ['nullable', 'numeric', 'gte:min_value'],
            'sort_by' => ['nullable', Rule::in(['id', 'total', 'status', 'created_at'])],
            'sort_dir' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
