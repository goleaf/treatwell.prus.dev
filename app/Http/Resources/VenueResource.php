<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VenueResource extends JsonResource
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
            'external_id' => $this->external_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'address' => $this->address,
            'type_id' => $this->type_id,
            'type_name' => $this->type_name,
            'normalised_name' => $this->normalised_name,
            'desktop_uri' => $this->desktop_uri,
            'mobile_uri' => $this->mobile_uri,
            'app_uri' => $this->app_uri,
            'is_new_venue' => $this->is_new_venue,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'opening_hours' => $this->opening_hours,
            'rating' => $this->rating,
            'rating_count' => $this->rating_count,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships
            'city' => $this->whenLoaded('city'),
            'location' => $this->whenLoaded('location'),
            'rating_details' => $this->whenLoaded('ratingDetails'),
            'images' => ImageResource::collection($this->whenLoaded('images')),
            'opening_hours_details' => OpeningHourResource::collection($this->whenLoaded('openingHours')),
            'treatments' => TreatmentResource::collection($this->whenLoaded('treatments')),
        ];
    }
}