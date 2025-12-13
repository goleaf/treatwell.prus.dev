<?php

namespace App\Http\Requests\Api;

use App\Models\OpeningHour;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOpeningHourRequest extends FormRequest
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
            'day_of_week' => ['required', Rule::in(OpeningHour::DAYS_OF_WEEK)],
            'opening_time' => ['nullable', 'date_format:H:i', 'required_if:is_open,true'],
            'closing_time' => ['nullable', 'date_format:H:i', 'required_if:is_open,true', 'after:opening_time'],
            'is_open' => ['boolean'],
        ];
    }
}
