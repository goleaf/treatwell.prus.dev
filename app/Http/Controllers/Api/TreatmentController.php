<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Treatment;
use App\Models\Venue;
use Illuminate\Http\Request;

class TreatmentController extends Controller
{
    /**
     * Get a list of all treatment categories
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
     * Get venues offering a specific treatment category or name
     */
    public function venuesByTreatment(Request $request)
    {
        $request->validate([
            'category' => 'nullable|string',
            'name' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = $request->input('per_page', 20);
        $category = $request->input('category');
        $name = $request->input('name');

        $venues = Venue::whereHas('treatments', function ($query) use ($category, $name) {
            if ($category) {
                $query->where('category_name', $category);
            }

            if ($name) {
                $query->where('name', 'like', "%{$name}%");
            }
        })
            ->with(['location.city', 'rating', 'images' => function ($query) {
                $query->where('is_primary', true);
            }])
            ->when($request->input('rating'), function ($query, $rating) {
                $query->whereHas('rating', function ($q) use ($rating) {
                    $q->where('weighted_average', '>=', $rating);
                });
            })
            ->when($request->input('city_id'), function ($query, $cityId) {
                $query->whereHas('location', function ($q) use ($cityId) {
                    $q->where('city_id', $cityId);
                });
            })
            ->paginate($perPage);

        return response()->json($venues);
    }

    /**
     * Get venues by price range for treatments
     */
    public function venuesByPriceRange(Request $request)
    {
        $request->validate([
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = $request->input('per_page', 20);
        $minPrice = $request->input('min_price');
        $maxPrice = $request->input('max_price');

        $venues = Venue::whereHas('treatments', function ($query) use ($minPrice, $maxPrice) {
            if ($minPrice !== null) {
                $query->where('min_price', '>=', $minPrice);
            }

            if ($maxPrice !== null) {
                $query->where('max_price', '<=', $maxPrice);
            }
        })
            ->with(['location.city', 'rating', 'images' => function ($query) {
                $query->where('is_primary', true);
            }, 'treatments' => function ($query) use ($minPrice, $maxPrice) {
                if ($minPrice !== null) {
                    $query->where('min_price', '>=', $minPrice);
                }

                if ($maxPrice !== null) {
                    $query->where('max_price', '<=', $maxPrice);
                }
            }])
            ->when($request->input('rating'), function ($query, $rating) {
                $query->whereHas('rating', function ($q) use ($rating) {
                    $q->where('weighted_average', '>=', $rating);
                });
            })
            ->when($request->input('city_id'), function ($query, $cityId) {
                $query->whereHas('location', function ($q) use ($cityId) {
                    $q->where('city_id', $cityId);
                });
            })
            ->paginate($perPage);

        return response()->json($venues);
    }

    /**
     * Get average, minimum and maximum prices for treatments
     */
    public function priceStats()
    {
        $stats = [
            'min_price' => Treatment::min('min_price'),
            'max_price' => Treatment::max('max_price'),
            'avg_price' => Treatment::avg('min_price'),
        ];

        return response()->json($stats);
    }
}
