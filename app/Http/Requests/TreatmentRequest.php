<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TreatmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // only allow updates if the user is logged in
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'venue_id' => ['required', 'exists:venues,id'],
            'external_id' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:treatments,slug,'.$this->route('treatment')?->id],
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

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'venue_id' => 'venue',
            'external_id' => 'external ID',
            'min_price' => 'minimum price',
            'max_price' => 'maximum price',
            'min_duration' => 'minimum duration',
            'max_duration' => 'maximum duration',
            'category_id' => 'category ID',
            'category_name' => 'category name',
            'is_active' => 'active status',
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
            'name.required' => 'The treatment name is required.',
            'slug.required' => 'The slug is required.',
            'slug.unique' => 'This slug is already taken.',
            'min_price.lte' => 'The minimum price must be less than or equal to the maximum price.',
            'max_price.gte' => 'The maximum price must be greater than or equal to the minimum price.',
            'min_duration.lte' => 'The minimum duration must be less than or equal to the maximum duration.',
            'max_duration.gte' => 'The maximum duration must be greater than or equal to the minimum duration.',
        ];
    }
}
