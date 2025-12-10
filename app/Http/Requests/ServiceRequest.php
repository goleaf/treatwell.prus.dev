<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ServiceRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:services,slug,'.$this->id],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:255'],
            'duration' => ['nullable', 'integer', 'min:1'],
            'price' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'min_price' => ['nullable', 'numeric', 'min:0', 'decimal:0,2', 'lte:max_price'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'decimal:0,2', 'gte:min_price'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'metadata' => ['nullable', 'array'],
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
            'min_price' => 'minimum price',
            'max_price' => 'maximum price',
            'is_active' => 'active status',
            'is_featured' => 'featured status',
            'sort_order' => 'sort order',
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
            'name.required' => 'The service name is required.',
            'slug.required' => 'The slug is required.',
            'slug.unique' => 'This slug is already taken.',
            'min_price.lte' => 'The minimum price must be less than or equal to the maximum price.',
            'max_price.gte' => 'The maximum price must be greater than or equal to the minimum price.',
        ];
    }
}
