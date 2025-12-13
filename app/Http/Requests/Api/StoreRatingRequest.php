<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreRatingRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'venue_id' => ['required', 'exists:venues,id'],
            'weighted_average' => ['nullable', 'numeric', 'between:0,5', 'decimal:0,2'],
            'count' => ['nullable', 'integer', 'min:0'],
            'cleanliness_avg' => ['nullable', 'numeric', 'between:0,5', 'decimal:0,2'],
            'cleanliness_count' => ['nullable', 'integer', 'min:0'],
            'staff_avg' => ['nullable', 'numeric', 'between:0,5', 'decimal:0,2'],
            'staff_count' => ['nullable', 'integer', 'min:0'],
            'atmosphere_avg' => ['nullable', 'numeric', 'between:0,5', 'decimal:0,2'],
            'atmosphere_count' => ['nullable', 'integer', 'min:0'],
            'display_average' => ['nullable', 'numeric', 'between:0,5', 'decimal:0,2'],
            'reviewer_name' => ['nullable', 'string', 'max:255'],
            'reviewer_email' => ['nullable', 'email', 'max:255'],
            'rating' => ['nullable', 'numeric', 'between:0,5', 'decimal:0,2'],
            'comment' => ['nullable', 'string'],
            'is_verified' => ['boolean'],
        ];
    }
}
