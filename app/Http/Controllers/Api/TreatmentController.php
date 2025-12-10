<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTreatmentRequest;
use App\Http\Requests\Api\UpdateTreatmentRequest;
use App\Http\Resources\TreatmentCollection;
use App\Http\Resources\TreatmentResource;
use App\Models\Treatment;
use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TreatmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Treatment::query();
        
        $query->with(['venue']);

        // Search
        if ($request->has('search') && $request->input('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        // Filter by category
        if ($request->has('category') && $request->input('category')) {
            $category = $request->input('category');
            $query->where('category_name', $category);
        }

        // Filter by venue
        if ($request->has('venue_id') && $request->input('venue_id')) {
            $venueId = $request->input('venue_id');
            $query->where('venue_id', $venueId);
        }

        // Filter by price range
        if ($request->has('min_price') && $request->input('min_price')) {
            $minPrice = $request->input('min_price');
            $query->where('min_price', '>=', $minPrice);
        }

        if ($request->has('max_price') && $request->input('max_price')) {
            $maxPrice = $request->input('max_price');
            $query->where('max_price', '<=', $maxPrice);
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
            case 'price':
                $query->orderBy('min_price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('min_price', 'desc');
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
        $treatments = $query->paginate($perPage);

        return new TreatmentCollection($treatments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTreatmentRequest $request)
    {
        $treatment = Treatment::create($request->validated());
        
        return (new TreatmentResource($treatment->load(['venue'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Treatment $treatment): TreatmentResource
    {
        return new TreatmentResource($treatment->load(['venue']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTreatmentRequest $request, Treatment $treatment): TreatmentResource
    {
        $treatment->update($request->validated());
        
        return new TreatmentResource($treatment->load(['venue']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Treatment $treatment): Response
    {
        $treatment->delete();
        
        return response()->noContent();
    }

    /**
     * Get distinct treatment categories.
     */
    public function categories()
    {
        $categories = Treatment::select('category_name')
            ->whereNotNull('category_name')
            ->distinct()
            ->orderBy('category_name')
            ->pluck('category_name');

        return response()->json($categories);
    }

    /**
     * Get venues filtered by treatment.
     */
    public function venuesByTreatment(Request $request)
    {
        $request->validate([
            'per_page' => 'nullable|integer|max:100',
        ]);

        $query = Venue::query();
        $query->with(['location.city', 'ratingDetails', 'images']);

        $perPage = $request->input('per_page', 15);

        // Filter by treatment category
        if ($request->has('category') && $request->input('category')) {
            $category = $request->input('category');
            $query->whereHas('treatments', function ($q) use ($category) {
                $q->where('category_name', $category);
            });
        }

        // Filter by treatment name
        if ($request->has('name') && $request->input('name')) {
            $name = $request->input('name');
            $query->whereHas('treatments', function ($q) use ($name) {
                $q->where('name', 'like', "%{$name}%");
            });
        }

        // Filter by rating
        if ($request->has('rating') && $request->input('rating')) {
            $rating = $request->input('rating');
            $query->whereHas('ratingDetails', function ($q) use ($rating) {
                $q->where('weighted_average', '>=', $rating);
            });
        }

        // Filter by city
        if ($request->has('city_id') && $request->input('city_id')) {
            $cityId = $request->input('city_id');
            $query->whereHas('location', function ($q) use ($cityId) {
                $q->where('city_id', $cityId);
            });
        }

        $venues = $query->paginate($perPage);

        return response()->json($venues);
    }

    /**
     * Get venues filtered by price range.
     */
    public function venuesByPriceRange(Request $request)
    {
        $request->validate([
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'per_page' => 'nullable|integer|max:100',
        ]);

        $query = Venue::query();
        $query->with(['location.city', 'ratingDetails', 'images']);

        $perPage = $request->input('per_page', 15);
        $minPrice = $request->input('min_price');
        $maxPrice = $request->input('max_price');

        if ($minPrice || $maxPrice) {
            $query->whereHas('treatments', function ($q) use ($minPrice, $maxPrice) {
                if ($minPrice) {
                    $q->where('min_price', '>=', $minPrice);
                }
                if ($maxPrice) {
                    $q->where('max_price', '<=', $maxPrice);
                }
            });
        }

        $venues = $query->paginate($perPage);

        return response()->json($venues);
    }

    /**
     * Get price statistics.
     */
    public function priceStats()
    {
        $stats = Treatment::selectRaw('MIN(min_price) as min_price, MAX(max_price) as max_price, AVG(min_price) as avg_price')
            ->first();

        return response()->json([
            'min_price' => (float) $stats->min_price,
            'max_price' => (float) $stats->max_price,
            'avg_price' => (float) $stats->avg_price,
        ]);
    }
}