<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CityRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:cities,slug,'.$this->id],
            'normalised_name' => ['nullable', 'string', 'max:255'],
            'entity_id' => ['nullable', 'string', 'max:255'],
            'is_main_city' => ['boolean'],
            'subregion' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'country_id' => ['required', 'exists:countries,id'],
            'main_city_id' => ['nullable', 'exists:cities,id'],
            'type' => ['nullable', 'string', 'max:255'],
            'radius_distance' => ['nullable', 'numeric', 'min:0'],
            'radius_unit' => ['nullable', 'string', 'max:10'],
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
            'normalised_name' => 'normalized name',
            'entity_id' => 'entity ID',
            'is_main_city' => 'main city status',
            'country_id' => 'country',
            'main_city_id' => 'main city',
            'radius_distance' => 'radius distance',
            'radius_unit' => 'radius unit',
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
            'name.required' => 'The city name is required.',
            'slug.required' => 'The slug is required.',
            'slug.unique' => 'This slug is already taken.',
            'country_id.required' => 'The country is required.',
            'country_id.exists' => 'The selected country does not exist.',
            'main_city_id.exists' => 'The selected main city does not exist.',
        ];
    }
}
