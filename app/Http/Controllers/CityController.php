<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CityController extends Controller
{
    /**
     * Display a listing of cities and subregions.
     */
    public function index()
    {
        // Get main cities with their subregions count
        $mainCities = City::where('is_main_city', true)
            ->withCount(['subregions', 'locations'])
            ->orderBy('name')
            ->get();
        
        // Calculate statistics
        $mainCitiesCount = City::where('is_main_city', true)->count();
        $subregionsCount = City::where('is_main_city', false)->count();
        $totalVenues = Venue::count();
        
        // Calculate average venues per city
        $avgVenuesPerCity = $mainCitiesCount > 0 ? $totalVenues / $mainCitiesCount : 0;
        
        // Get top cities by venue count (including subregions)
        $topCitiesByVenues = DB::table('cities AS c')
            ->select('c.id', 'c.name', DB::raw('COUNT(l.id) as venues_count'))
            ->leftJoin('cities AS s', 's.main_city_id', '=', 'c.id')
            ->leftJoin('locations AS l', function($join) {
                $join->on('l.city_id', '=', 'c.id')
                    ->orOn('l.city_id', '=', 's.id');
            })
            ->where('c.is_main_city', true)
            ->groupBy('c.id', 'c.name')
            ->orderBy('venues_count', 'desc')
            ->limit(5)
            ->get();
        
        return view('cities.index', compact(
            'mainCities',
            'mainCitiesCount',
            'subregionsCount',
            'totalVenues',
            'avgVenuesPerCity',
            'topCitiesByVenues'
        ));
    }
    
    /**
     * Display a specific city and its subregions.
     */
    public function show(City $city)
    {
        // If this is not a main city, redirect to the main city
        if (!$city->is_main_city && $city->mainCity) {
            return redirect()->route('cities.show', $city->mainCity)
                ->with('info', "Redirected to main city: {$city->mainCity->name}");
        }
        
        // Load subregions
        $city->load('subregions');
        
        // Get venues in this city (including subregions)
        $venues = $city->getAllVenues()
            ->with(['location.city', 'rating', 'images' => function($query) {
                $query->where('is_primary', true);
            }])
            ->paginate(20);
        
        return view('cities.show', compact('city', 'venues'));
    }
    
    /**
     * Display a listing of main cities.
     */
    public function mainCities()
    {
        $cities = City::where('is_main_city', true)
            ->withCount('subregions')
            ->orderBy('name')
            ->get();
        
        return view('cities.main', compact('cities'));
    }
    
    /**
     * Display a listing of subregions.
     */
    public function subregions()
    {
        $subregions = City::where('is_main_city', false)
            ->with('mainCity')
            ->orderBy('subregion')
            ->paginate(20);
        
        return view('cities.subregions', compact('subregions'));
    }
}
