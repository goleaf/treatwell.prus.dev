<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Treatment;
use App\Models\Venue;
use Illuminate\Http\Request;

class TreatmentController extends Controller
{
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
        $query->with(['location.city', 'rating', 'images']);

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
            $query->whereHas('rating', function ($q) use ($rating) {
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
        $query->with(['location.city', 'rating', 'images']);

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
