<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImageRequest extends FormRequest
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
            'imageable_type' => ['required', 'string', 'max:255'],
            'imageable_id' => ['required', 'integer', 'min:1'],
            'venue_id' => ['nullable', 'exists:venues,id'],
            'external_id' => ['nullable', 'string', 'max:255'],
            'path' => ['required', 'string', 'max:500'],
            'uri_small' => ['nullable', 'url', 'max:500'],
            'uri_medium' => ['nullable', 'url', 'max:500'],
            'uri_large' => ['nullable', 'url', 'max:500'],
            'uri_xlarge' => ['nullable', 'url', 'max:500'],
            'is_primary' => ['boolean'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
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
            'imageable_type' => 'model type',
            'imageable_id' => 'model ID',
            'venue_id' => 'venue',
            'external_id' => 'external ID',
            'uri_small' => 'small image URL',
            'uri_medium' => 'medium image URL',
            'uri_large' => 'large image URL',
            'uri_xlarge' => 'extra large image URL',
            'is_primary' => 'primary image',
            'alt_text' => 'alternative text',
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
            'imageable_type.required' => 'The model type is required.',
            'imageable_id.required' => 'The model ID is required.',
            'path.required' => 'The image path is required.',
            'venue_id.exists' => 'The selected venue does not exist.',
        ];
    }
}
