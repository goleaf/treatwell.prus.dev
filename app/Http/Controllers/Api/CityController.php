<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\Request;

class CityController extends Controller
{
    /**
     * Get all cities
     */
    public function index()
    {
        $cities = City::orderBy('name')->get();
        
        return response()->json($cities);
    }
    
    /**
     * Get only main cities
     */
    public function mainCities()
    {
        $cities = City::where('is_main_city', true)
            ->orderBy('name')
            ->get();
        
        return response()->json($cities);
    }
    
    /**
     * Get subregions for a specific main city
     */
    public function subregions($cityId)
    {
        $city = City::findOrFail($cityId);
        
        if (!$city->is_main_city) {
            return response()->json(['error' => 'This is not a main city'], 400);
        }
        
        $subregions = City::where('main_city_id', $city->id)
            ->orderBy('name')
            ->get();
        
        return response()->json($subregions);
    }
    
    /**
     * Get all subregions
     */
    public function allSubregions()
    {
        $subregions = City::select('subregion')
            ->whereNotNull('subregion')
            ->distinct()
            ->pluck('subregion')
            ->sort()
            ->values();
        
        return response()->json($subregions);
    }
    
    /**
     * Search cities by name or subregion
     */
    public function search(Request $request)
    {
        $query = $request->get('query', '');
        
        if (empty($query)) {
            return response()->json([]);
        }
        
        $cities = City::where('name', 'like', "%{$query}%")
            ->orWhere('subregion', 'like', "%{$query}%")
            ->orderBy('name')
            ->limit(10)
            ->get();
        
        return response()->json($cities);
    }
}
