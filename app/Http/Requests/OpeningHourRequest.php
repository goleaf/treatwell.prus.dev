<?php

namespace App\Http\Requests;

use App\Models\OpeningHour;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OpeningHourRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // only allow updates if the user is logged in
        return backpack_auth()->check();
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
            'day_of_week' => ['required', Rule::in(OpeningHour::DAYS_OF_WEEK)],
            'opening_time' => ['nullable', 'date_format:H:i', 'required_if:is_open,true'],
            'closing_time' => ['nullable', 'date_format:H:i', 'required_if:is_open,true', 'after:opening_time'],
            'is_open' => ['boolean'],
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
            'day_of_week' => 'day of week',
            'opening_time' => 'opening time',
            'closing_time' => 'closing time',
            'is_open' => 'open status',
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
            'day_of_week.required' => 'The day of week is required.',
            'day_of_week.in' => 'The day of week must be a valid day.',
            'opening_time.required_if' => 'The opening time is required when the venue is open.',
            'closing_time.required_if' => 'The closing time is required when the venue is open.',
            'closing_time.after' => 'The closing time must be after the opening time.',
        ];
    }
}
