<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\HandlesApiErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreLocationRequest;
use App\Http\Requests\Api\UpdateLocationRequest;
use App\Http\Resources\LocationResource;
use App\Models\City;
use App\Models\Location;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    use AuthorizesRequests, HandlesApiErrors;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Location::query();

        $query->with(['venue', 'city']);

        // Search
        if ($request->has('search') && $request->input('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('address_line1', 'like', "%{$search}%")
                ->orWhere('address_line2', 'like', "%{$search}%");
        }

        // Filter by venue
        if ($request->has('venue_id') && $request->input('venue_id')) {
            $venueId = $request->input('venue_id');
            $query->where('venue_id', $venueId);
        }

        // Filter by city
        if ($request->has('city_id') && $request->input('city_id')) {
            $cityId = $request->input('city_id');
            $query->where('city_id', $cityId);
        }

        // Filter by active status
        if ($request->has('active') && $request->input('active') !== null) {
            $active = filter_var($request->input('active'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $active);
        }

        // Filter by postal code
        if ($request->has('postal_code') && $request->input('postal_code')) {
            $postalCode = $request->input('postal_code');
            $query->where('postal_code', 'like', "%{$postalCode}%");
        }

        // Filter by coordinates (locations with lat/lng)
        if ($request->has('has_coordinates') && $request->input('has_coordinates') !== null) {
            $hasCoordinates = filter_var($request->input('has_coordinates'), FILTER_VALIDATE_BOOLEAN);
            if ($hasCoordinates) {
                $query->whereNotNull('latitude')->whereNotNull('longitude');
            } else {
                $query->where(function ($q) {
                    $q->whereNull('latitude')->orWhereNull('longitude');
                });
            }
        }

        // Sorting
        $sort = $request->input('sort', 'name');
        switch ($sort) {
            case 'name':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'city':
                $query->join('cities', 'locations.city_id', '=', 'cities.id')
                    ->orderBy('cities.name', 'asc')
                    ->select('locations.*');
                break;
            case 'postal_code':
                $query->orderBy('postal_code', 'asc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            default:
                $query->orderBy('name', 'asc');
        }

        $perPage = $request->input('per_page', 15);
        $locations = $query->paginate($perPage);

        return LocationResource::collection($locations);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLocationRequest $request)
    {
        $data = $request->validated();

        // Validate related models exist
        $relationshipError = $this->validateRelatedModels($data, [
            'venue_id' => Venue::class,
            'city_id' => City::class,
        ]);

        if ($relationshipError) {
            return $relationshipError;
        }

        $location = $this->executeInTransaction(function () use ($data) {
            $location = Location::create($data);
            $this->logApiOperation('create', $location, $data);

            return $location;
        });

        if ($location instanceof JsonResponse) {
            return $location;
        }

        return (new LocationResource($location->load(['venue', 'city'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Location $location): LocationResource
    {
        return new LocationResource($location->load(['venue', 'city']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLocationRequest $request, Location $location)
    {
        $data = $request->validated();

        // Check for concurrent modifications
        $concurrencyError = $this->checkConcurrentModification($location, $request->header('If-Unmodified-Since'));
        if ($concurrencyError) {
            return $concurrencyError;
        }

        // Validate related models exist
        $relationshipError = $this->validateRelatedModels($data, [
            'venue_id' => Venue::class,
            'city_id' => City::class,
        ]);

        if ($relationshipError) {
            return $relationshipError;
        }

        $updatedLocation = $this->executeInTransaction(function () use ($location, $data) {
            $location->update($data);
            $this->logApiOperation('update', $location, $data);

            return $location;
        });

        if ($updatedLocation instanceof JsonResponse) {
            return $updatedLocation;
        }

        return new LocationResource($updatedLocation->load(['venue', 'city']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Location $location)
    {
        // Validate that the location can be deleted
        $deletionError = $this->validateDeletion($location);
        if ($deletionError) {
            return $deletionError;
        }

        $result = $this->executeInTransaction(function () use ($location) {
            $this->logApiOperation('delete', $location);
            $location->delete();

            return true;
        });

        if ($result instanceof JsonResponse) {
            return $result;
        }

        return response()->noContent();
    }

    /**
     * Get critical relationships that prevent deletion.
     */
    protected function getCriticalRelationships(Model $model): array
    {
        // Locations typically don't have critical relationships that prevent deletion
        return [];
    }

    /**
     * Get only active locations.
     */
    public function active()
    {
        $locations = Location::active()
            ->with(['venue', 'city'])
            ->orderBy('name')
            ->get();

        return LocationResource::collection($locations);
    }

    /**
     * Get locations within a radius of given coordinates.
     */
    public function nearby(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:0.1|max:100', // radius in km
        ]);

        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $radius = $request->input('radius', 10); // default 10km

        // Using Haversine formula to calculate distance
        $locations = Location::selectRaw('
            *,
            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance
        ', [$latitude, $longitude, $latitude])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->having('distance', '<=', $radius)
            ->with(['venue', 'city'])
            ->orderBy('distance')
            ->get();

        return LocationResource::collection($locations);
    }

    /**
     * Get locations by city.
     */
    public function byCity(Request $request, $cityId)
    {
        $request->validate([
            'city_id' => 'exists:cities,id',
        ]);

        $locations = Location::where('city_id', $cityId)
            ->with(['venue', 'city'])
            ->orderBy('name')
            ->get();

        return LocationResource::collection($locations);
    }

    /**
     * Get locations with coordinates.
     */
    public function withCoordinates()
    {
        $locations = Location::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with(['venue', 'city'])
            ->orderBy('name')
            ->get();

        return LocationResource::collection($locations);
    }
}
