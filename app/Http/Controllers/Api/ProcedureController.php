<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreProcedureRequest;
use App\Http\Requests\Api\UpdateProcedureRequest;
use App\Http\Resources\ProcedureCollection;
use App\Http\Resources\ProcedureResource;
use App\Models\Procedure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProcedureController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Procedure::query();

        $query->with(['venues', 'cities']);

        // Search
        if ($request->has('search') && $request->input('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        }

        // Filter by category
        if ($request->has('category') && $request->input('category')) {
            $category = $request->input('category');
            $query->where('category', $category);
        }

        // Filter by active status
        if ($request->has('active') && $request->input('active') !== null) {
            $active = filter_var($request->input('active'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $active);
        }

        // Filter by city
        if ($request->has('city_id') && $request->input('city_id')) {
            $cityId = $request->input('city_id');
            $query->whereHas('cities', function ($q) use ($cityId) {
                $q->where('cities.id', $cityId);
            });
        }

        // Filter by venue
        if ($request->has('venue_id') && $request->input('venue_id')) {
            $venueId = $request->input('venue_id');
            $query->whereHas('venues', function ($q) use ($venueId) {
                $q->where('venues.id', $venueId);
            });
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
            case 'category':
                $query->orderBy('category', 'asc');
                break;
            case 'category_desc':
                $query->orderBy('category', 'desc');
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
        $procedures = $query->paginate($perPage);

        return new ProcedureCollection($procedures);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProcedureRequest $request)
    {
        $procedure = Procedure::create($request->validated());

        return (new ProcedureResource($procedure->load(['venues', 'cities'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Procedure $procedure): ProcedureResource
    {
        return new ProcedureResource($procedure->load(['venues', 'cities']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProcedureRequest $request, Procedure $procedure): ProcedureResource
    {
        $procedure->update($request->validated());

        return new ProcedureResource($procedure->load(['venues', 'cities']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Procedure $procedure): Response
    {
        $procedure->delete();

        return response()->noContent();
    }

    /**
     * Get distinct procedure categories.
     */
    public function categories()
    {
        $categories = Procedure::select('category')
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return response()->json($categories);
    }

    /**
     * Get only active procedures.
     */
    public function active()
    {
        $procedures = Procedure::active()
            ->with(['venues', 'cities'])
            ->orderBy('name')
            ->get();

        return new ProcedureCollection($procedures);
    }

    /**
     * Get procedures with venues count.
     */
    public function withVenuesCount()
    {
        $procedures = Procedure::withCount('venues')
            ->orderBy('name')
            ->get();

        return response()->json($procedures);
    }

    /**
     * Get procedures with cities count.
     */
    public function withCitiesCount()
    {
        $procedures = Procedure::withCount('cities')
            ->orderBy('name')
            ->get();

        return response()->json($procedures);
    }
}
