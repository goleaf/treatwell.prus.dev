<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreVenueRequest;
use App\Http\Requests\Api\UpdateVenueRequest;
use App\Http\Resources\VenueCollection;
use App\Http\Resources\VenueResource;
use App\Models\City;
use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class VenueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
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
        $venue = Venue::create($request->validated());
        
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

        return new VenueResource($venue);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVenueRequest $request, Venue $venue): VenueResource
    {
        $venue->update($request->validated());
        
        return new VenueResource($venue->load(['location.city', 'ratingDetails', 'images', 'openingHours', 'treatments']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Venue $venue): Response
    {
        $venue->delete();
        
        return response()->noContent();
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