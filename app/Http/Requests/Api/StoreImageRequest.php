<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreImageRequest extends FormRequest
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
}
