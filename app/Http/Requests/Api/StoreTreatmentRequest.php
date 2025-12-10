<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreTreatmentRequest extends FormRequest
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
            'external_id' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:treatments,slug'],
            'description' => ['nullable', 'string'],
            'duration' => ['nullable', 'integer', 'min:1'],
            'price' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'min_price' => ['nullable', 'numeric', 'min:0', 'decimal:0,2', 'lte:max_price'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'decimal:0,2', 'gte:min_price'],
            'min_duration' => ['nullable', 'integer', 'min:1', 'lte:max_duration'],
            'max_duration' => ['nullable', 'integer', 'min:1', 'gte:min_duration'],
            'category_id' => ['nullable', 'integer'],
            'category_name' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'options' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ];
    }
}
