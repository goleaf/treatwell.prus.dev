<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCityRequest;
use App\Http\Requests\Api\UpdateCityRequest;
use App\Http\Resources\CityCollection;
use App\Http\Resources\CityResource;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = City::query();
        
        $query->with(['country', 'locations']);

        // Search
        if ($request->has('search') && $request->input('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        // Filter by country
        if ($request->has('country_id') && $request->input('country_id')) {
            $countryId = $request->input('country_id');
            $query->where('country_id', $countryId);
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
        $cities = $query->paginate($perPage);

        return new CityCollection($cities);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCityRequest $request)
    {
        $city = City::create($request->validated());
        
        return (new CityResource($city->load(['country', 'locations'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(City $city): CityResource
    {
        return new CityResource($city->load(['country', 'locations']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCityRequest $request, City $city): CityResource
    {
        $city->update($request->validated());
        
        return new CityResource($city->load(['country', 'locations']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(City $city): Response
    {
        $city->delete();
        
        return response()->noContent();
    }
}
