<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\HandlesApiErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCountryRequest;
use App\Http\Requests\Api\UpdateCountryRequest;
use App\Http\Resources\CountryCollection;
use App\Http\Resources\CountryResource;
use App\Models\Country;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    use AuthorizesRequests, HandlesApiErrors;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Country::class);
        $query = Country::query();

        $query->with(['cities']);

        // Search
        if ($request->has('search') && $request->input('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%");
        }

        // Filter by active status
        if ($request->has('active') && $request->input('active') !== null) {
            $active = filter_var($request->input('active'), FILTER_VALIDATE_BOOLEAN);
            $query->where('active', $active);
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
            case 'code':
                $query->orderBy('code', 'asc');
                break;
            case 'code_desc':
                $query->orderBy('code', 'desc');
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
        $countries = $query->paginate($perPage);

        return new CountryCollection($countries);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCountryRequest $request)
    {
        $this->authorize('create', Country::class);

        $data = $request->validated();

        $country = $this->executeInTransaction(function () use ($data) {
            $country = Country::create($data);
            $this->logApiOperation('create', $country, $data);

            return $country;
        });

        if ($country instanceof JsonResponse) {
            return $country;
        }

        return (new CountryResource($country->load(['cities'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Country $country): CountryResource
    {
        $this->authorize('view', $country);

        return new CountryResource($country->load(['cities']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCountryRequest $request, Country $country)
    {
        $this->authorize('update', $country);

        $data = $request->validated();

        // Check for concurrent modifications
        $concurrencyError = $this->checkConcurrentModification($country, $request->header('If-Unmodified-Since'));
        if ($concurrencyError) {
            return $concurrencyError;
        }

        $updatedCountry = $this->executeInTransaction(function () use ($country, $data) {
            $country->update($data);
            $this->logApiOperation('update', $country, $data);

            return $country;
        });

        if ($updatedCountry instanceof JsonResponse) {
            return $updatedCountry;
        }

        return new CountryResource($updatedCountry->load(['cities']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Country $country)
    {
        $this->authorize('delete', $country);

        // Validate that the country can be deleted
        $deletionError = $this->validateDeletion($country);
        if ($deletionError) {
            return $deletionError;
        }

        $result = $this->executeInTransaction(function () use ($country) {
            $this->logApiOperation('delete', $country);
            $country->delete();

            return true;
        });

        if ($result instanceof JsonResponse) {
            return $result;
        }

        return response()->noContent();
    }

    /**
     * Get critical relationships that prevent deletion.
     */
    protected function getCriticalRelationships(Model $model): array
    {
        return ['cities'];
    }

    /**
     * Get only active countries.
     */
    public function active()
    {
        $countries = Country::active()
            ->orderBy('name')
            ->get();

        return response()->json($countries);
    }

    /**
     * Get countries with cities count.
     */
    public function withCitiesCount()
    {
        $countries = Country::withCount('cities')
            ->orderBy('name')
            ->get();

        return response()->json($countries);
    }
}
