<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'category' => $this->category,
            'duration' => $this->duration,
            'price' => $this->price,
            'min_price' => $this->min_price,
            'max_price' => $this->max_price,
            'price_range' => $this->price_range,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'sort_order' => $this->sort_order,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships
            'venue' => $this->whenLoaded('venue'),
            'treatments' => TreatmentResource::collection($this->whenLoaded('treatments')),
            'images' => ImageResource::collection($this->whenLoaded('images')),
            'ratings' => RatingResource::collection($this->whenLoaded('ratings')),
        ];
    }
}
