<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\HandlesApiErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreVenueRequest;
use App\Http\Requests\Api\UpdateVenueRequest;
use App\Http\Resources\VenueCollection;
use App\Http\Resources\VenueResource;
use App\Models\City;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    use AuthorizesRequests, HandlesApiErrors;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Venue::class);
        $query = Venue::query();

        $query->with(['location.city', 'ratingDetails', 'images']);

        // Search
        if ($request->has('search') && $request->input('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        // Filter by city
        if ($request->has('city_id') && $request->input('city_id')) {
            $cityId = $request->input('city_id');
            $query->whereHas('location', function ($q) use ($cityId) {
                $q->where('city_id', $cityId);
            });
        }

        // Filter by rating
        if ($request->has('rating') && $request->input('rating')) {
            $rating = $request->input('rating');
            $query->whereHas('ratingDetails', function ($q) use ($rating) {
                $q->where('weighted_average', '>=', $rating);
            });
        }

        // Filter by type
        if ($request->has('type') && $request->input('type')) {
            $type = $request->input('type');
            $query->where('type_name', $type);
        }

        // Sorting
        if ($request->has('sort')) {
            $sort = $request->input('sort');
            switch ($sort) {
                case 'name':
                    $query->orderBy('name', 'asc');
                    break;
                case 'name_desc':
                    $query->orderBy('name', 'desc');
                    break;
                case 'rating':
                    $query->join('ratings', 'venues.id', '=', 'ratings.venue_id')
                        ->orderBy('ratings.weighted_average', 'desc')
                        ->select('venues.*'); // Avoid column collision
                    break;
                case 'rating_asc':
                    $query->join('ratings', 'venues.id', '=', 'ratings.venue_id')
                        ->orderBy('ratings.weighted_average', 'asc')
                        ->select('venues.*');
                    break;
                case 'newest':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'oldest':
                    $query->orderBy('created_at', 'asc');
                    break;
                default:
                    $query->orderBy('id', 'desc');
            }
        } else {
            $query->orderBy('id', 'desc');
        }

        $perPage = $request->input('per_page', 15);
        $venues = $query->paginate($perPage);

        return new VenueCollection($venues);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVenueRequest $request)
    {
        $this->authorize('create', Venue::class);

        $data = $request->validated();

        // Validate related models exist
        $relationshipError = $this->validateRelatedModels($data, [
            'city_id' => City::class,
        ]);

        if ($relationshipError) {
            return $relationshipError;
        }

        $venue = $this->executeInTransaction(function () use ($data) {
            $venue = Venue::create($data);
            $this->logApiOperation('create', $venue, $data);

            return $venue;
        });

        if ($venue instanceof JsonResponse) {
            return $venue;
        }

        return (new VenueResource($venue->load(['location.city', 'ratingDetails', 'images', 'openingHours', 'treatments'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id): VenueResource
    {
        // If $id is numeric, find by ID. Otherwise find by slug.
        if (is_numeric($id)) {
            $venue = Venue::with(['location.city', 'ratingDetails', 'images', 'openingHours', 'treatments'])->find($id);
        } else {
            $venue = Venue::with(['location.city', 'ratingDetails', 'images', 'openingHours', 'treatments'])->where('slug', $id)->first();
        }

        if (! $venue) {
            abort(404, 'Venue not found');
        }

        $this->authorize('view', $venue);

        return new VenueResource($venue);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVenueRequest $request, Venue $venue)
    {
        $this->authorize('update', $venue);

        $data = $request->validated();

        // Check for concurrent modifications
        $concurrencyError = $this->checkConcurrentModification($venue, $request->header('If-Unmodified-Since'));
        if ($concurrencyError) {
            return $concurrencyError;
        }

        // Validate related models exist
        $relationshipError = $this->validateRelatedModels($data, [
            'city_id' => City::class,
        ]);

        if ($relationshipError) {
            return $relationshipError;
        }

        $updatedVenue = $this->executeInTransaction(function () use ($venue, $data) {
            $venue->update($data);
            $this->logApiOperation('update', $venue, $data);

            return $venue;
        });

        if ($updatedVenue instanceof JsonResponse) {
            return $updatedVenue;
        }

        return new VenueResource($updatedVenue->load(['location.city', 'ratingDetails', 'images', 'openingHours', 'treatments']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Venue $venue)
    {
        $this->authorize('delete', $venue);

        // Validate that the venue can be deleted
        $deletionError = $this->validateDeletion($venue);
        if ($deletionError) {
            return $deletionError;
        }

        $result = $this->executeInTransaction(function () use ($venue) {
            $this->logApiOperation('delete', $venue);
            $venue->delete();

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
        return ['treatments', 'images', 'openingHours'];
    }

    /**
     * Get distinct venue types
     */
    public function types()
    {
        $types = Venue::select('type_name')
            ->whereNotNull('type_name')
            ->distinct()
            ->orderBy('type_name')
            ->pluck('type_name');

        return response()->json($types);
    }

    /**
     * Get all cities
     */
    public function cities()
    {
        $cities = City::select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($cities);
    }

    /**
     * Get statistics
     */
    public function stats()
    {
        $totalVenues = Venue::count();
        $totalCities = City::count();

        $topRatedVenues = Venue::join('ratings', 'venues.id', '=', 'ratings.venue_id')
            ->orderBy('ratings.weighted_average', 'desc')
            ->take(5)
            ->select('venues.id', 'venues.name', 'ratings.weighted_average')
            ->get();

        $citiesWithMostVenues = City::withCount('locations as venues_count')
            ->orderBy('venues_count', 'desc')
            ->take(5)
            ->get(['id', 'name', 'venues_count']);

        return response()->json([
            'total_venues' => $totalVenues,
            'total_cities' => $totalCities,
            'top_rated_venues' => $topRatedVenues,
            'cities_with_most_venues' => $citiesWithMostVenues,
        ]);
    }
}
