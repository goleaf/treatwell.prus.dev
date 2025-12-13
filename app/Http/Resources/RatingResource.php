<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RatingResource extends JsonResource
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
            'weighted_average' => $this->weighted_average,
            'count' => $this->count,
            'cleanliness_avg' => $this->cleanliness_avg,
            'cleanliness_count' => $this->cleanliness_count,
            'staff_avg' => $this->staff_avg,
            'staff_count' => $this->staff_count,
            'atmosphere_avg' => $this->atmosphere_avg,
            'atmosphere_count' => $this->atmosphere_count,
            'display_average' => $this->display_average,
            'reviewer_name' => $this->reviewer_name,
            'reviewer_email' => $this->reviewer_email,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'is_verified' => $this->is_verified,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships
            'venue' => $this->whenLoaded('venue'),
        ];
    }
}
