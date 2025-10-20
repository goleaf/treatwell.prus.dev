<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Venue;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    /**
     * Get a list of venues with pagination
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        
        $venues = Venue::with(['location.city', 'rating', 'images' => function($query) {
            $query->where('is_primary', true);
        }])
        ->when($request->city_id, function($query, $cityId) {
            $query->whereHas('location', function($q) use ($cityId) {
                $q->where('city_id', $cityId);
            });
        })
        ->when($request->rating, function($query, $rating) {
            $query->whereHas('rating', function($q) use ($rating) {
                $q->where('weighted_average', '>=', $rating);
            });
        })
        ->when($request->search, function($query, $search) {
            $query->where('name', 'like', "%{$search}%");
        })
        ->when($request->type, function($query, $type) {
            $query->where('type_name', $type);
        })
        ->when($request->sort, function($query, $sort) {
            switch ($sort) {
                case 'name':
                    $query->orderBy('name', 'asc');
                    break;
                case 'name_desc':
                    $query->orderBy('name', 'desc');
                    break;
                case 'rating':
                    $query->whereHas('rating', function($q) {
                        $q->orderBy('weighted_average', 'desc');
                    });
                    break;
                case 'rating_asc':
                    $query->whereHas('rating', function($q) {
                        $q->orderBy('weighted_average', 'asc');
                    });
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
        }, function($query) {
            $query->orderBy('name', 'asc');
        })
        ->paginate($perPage);
        
        return response()->json($venues);
    }
    
    /**
     * Get a single venue with full details
     */
    public function show(Venue $venue)
    {
        $venue->load(['location.city', 'rating', 'images', 'openingHours', 'treatments']);
        
        return response()->json($venue);
    }
    
    /**
     * Get venues by city
     */
    public function byCity(City $city, Request $request)
    {
        $perPage = $request->input('per_page', 20);
        
        $venues = Venue::whereHas('location', function($query) use ($city) {
            $query->where('city_id', $city->id);
        })
        ->with(['location.city', 'rating', 'images' => function($query) {
            $query->where('is_primary', true);
        }])
        ->when($request->rating, function($query, $rating) {
            $query->whereHas('rating', function($q) use ($rating) {
                $q->where('weighted_average', '>=', $rating);
            });
        })
        ->when($request->search, function($query, $search) {
            $query->where('name', 'like', "%{$search}%");
        })
        ->when($request->type, function($query, $type) {
            $query->where('type_name', $type);
        })
        ->when($request->sort, function($query, $sort) {
            switch ($sort) {
                case 'name':
                    $query->orderBy('name', 'asc');
                    break;
                case 'name_desc':
                    $query->orderBy('name', 'desc');
                    break;
                case 'rating':
                    $query->whereHas('rating', function($q) {
                        $q->orderBy('weighted_average', 'desc');
                    });
                    break;
                case 'rating_asc':
                    $query->whereHas('rating', function($q) {
                        $q->orderBy('weighted_average', 'asc');
                    });
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
        }, function($query) {
            $query->orderBy('name', 'asc');
        })
        ->paginate($perPage);
        
        return response()->json($venues);
    }
    
    /**
     * Get list of available cities
     */
    public function cities()
    {
        $cities = City::withMostVenues()
            ->get();
            
        return response()->json($cities);
    }
    
    /**
     * Get list of venue types
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
     * Get statistics
     */
    public function stats()
    {
        $totalVenues = Venue::count();
        $totalCities = City::has('locations')->count();
        
        $topRatedVenues = Venue::whereHas('rating', function($query) {
                $query->orderBy('weighted_average', 'desc');
            })
            ->with(['location.city', 'rating'])
            ->take(10)
            ->get();
        
        $citiesWithMostVenues = City::withMostVenues(10)->get();
            
        return response()->json([
            'total_venues' => $totalVenues,
            'total_cities' => $totalCities,
            'top_rated_venues' => $topRatedVenues,
            'cities_with_most_venues' => $citiesWithMostVenues
        ]);
    }
}
