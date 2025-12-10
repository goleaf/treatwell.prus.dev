<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LocationRequest extends FormRequest
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
            'external_id' => ['nullable', 'string', 'max:255'],
            'venue_id' => ['required', 'exists:venues,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'map_zoom' => ['nullable', 'integer', 'min:1', 'max:20'],
            'capacity' => ['nullable', 'integer', 'min:1'],
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
            'external_id' => 'external ID',
            'venue_id' => 'venue',
            'city_id' => 'city',
            'postal_code' => 'postal code',
            'address_line1' => 'address line 1',
            'address_line2' => 'address line 2',
            'map_zoom' => 'map zoom level',
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
            'city_id.exists' => 'The selected city does not exist.',
            'name.required' => 'The location name is required.',
        ];
    }
}
