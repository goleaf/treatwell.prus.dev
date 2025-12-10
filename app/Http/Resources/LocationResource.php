<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'venue_id' => $this->venue_id,
            'city_id' => $this->city_id,
            'external_id' => $this->external_id,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'postal_code' => $this->postal_code,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships
            'venue' => $this->whenLoaded('venue'),
            'city' => $this->whenLoaded('city'),
        ];
    }
}