<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use App\Models\Procedure;
use App\Models\Venue;
use App\Services\VenueService;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    protected VenueService $venueService;

    public function __construct(VenueService $venueService)
    {
        $this->venueService = $venueService;
    }

    /**
     * Display a listing of the venues.
     */
    public function index(Request $request)
    {
        $query = Venue::query();
        
        // Apply search filter
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }
        
        // Apply type filter
        if ($request->has('type') && !empty($request->input('type'))) {
            $query->where('type_name', $request->input('type'));
        }
        
        // Sort by name by default
        $query->orderBy('name');
        
        $venues = $query->paginate(15)->withQueryString();
        
        return view('venues.index', compact('venues'));
    }

    /**
     * Display the specified venue by slug.
     */
    public function showBySlug(string $slug)
    {
        $venue = Venue::where('normalised_name', $slug)->firstOrFail();
        
        return view('venues.show', compact('venue'));
    }
    
    /**
     * Display venues by city.
     */
    public function byCity(City $city, Request $request)
    {
        $query = Venue::with(['location.city', 'rating', 'images' => function($query) {
            $query->where('is_primary', true);
        }]);
        
        if ($city->is_main_city && ($request->include_subregions ?? true)) {
            // Include main city and all its subregions
            $cityIds = $city->subregions()->pluck('id')->push($city->id);
            $query->whereHas('location', function($q) use ($cityIds) {
                $q->whereIn('city_id', $cityIds);
            });
        } else {
            // Just this specific city
            $query->whereHas('location', function($q) use ($city) {
                $q->where('city_id', $city->id);
            });
        }
        
        $venues = $query
        ->when($request->subregion, function($query, $subregion) {
            $query->whereHas('location.city', function($q) use ($subregion) {
                $q->where('subregion', $subregion);
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
        ->paginate(20)
        ->withQueryString();
        
        // Get main cities for the dropdown
        $mainCities = City::where('is_main_city', true)->orderBy('name')->get();
        
        // Get all cities
        $cities = City::orderBy('name')->get();
        
        // Get unique subregions for this city
        $subregions = null;
        if ($city->is_main_city) {
            $subregions = City::where('main_city_id', $city->id)->distinct()->pluck('subregion')->filter()->sort();
        }
        
        return view('venues.index', compact('venues', 'cities', 'mainCities', 'city', 'subregions'));
    }
    
    /**
     * Display the dashboard with statistics.
     */
    public function dashboard()
    {
        $totalVenues = Venue::count();
        $totalCities = City::count();
        
        $topRatedVenues = Venue::whereHas('rating', function($query) {
            $query->orderBy('weighted_average', 'desc');
        })
        ->with(['location.city', 'rating'])
        ->take(10)
        ->get();
        
        $citiesWithMostVenues = City::withMostVenues(10)->get();
        
        return view('dashboard', compact(
            'totalVenues', 
            'totalCities', 
            'topRatedVenues',
            'citiesWithMostVenues'
        ));
    }
}
