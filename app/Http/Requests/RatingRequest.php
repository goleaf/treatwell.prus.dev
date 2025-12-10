<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RatingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // only allow updates if the user is logged in
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
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

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'venue_id' => 'venue',
            'weighted_average' => 'weighted average rating',
            'cleanliness_avg' => 'cleanliness average',
            'cleanliness_count' => 'cleanliness rating count',
            'staff_avg' => 'staff average',
            'staff_count' => 'staff rating count',
            'atmosphere_avg' => 'atmosphere average',
            'atmosphere_count' => 'atmosphere rating count',
            'display_average' => 'display average',
            'reviewer_name' => 'reviewer name',
            'reviewer_email' => 'reviewer email',
            'is_verified' => 'verified status',
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'venue_id.required' => 'The venue is required.',
            'venue_id.exists' => 'The selected venue does not exist.',
            'reviewer_email.email' => 'Please enter a valid email address.',
        ];
    }
}
