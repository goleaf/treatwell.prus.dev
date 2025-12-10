<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\HandlesApiErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCityRequest;
use App\Http\Requests\Api\UpdateCityRequest;
use App\Http\Resources\CityCollection;
use App\Http\Resources\CityResource;
use App\Models\City;
use App\Models\Country;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CityController extends Controller
{
    use AuthorizesRequests, HandlesApiErrors;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', City::class);
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
        $this->authorize('create', City::class);

        $data = $request->validated();

        // Validate related models exist
        $relationshipError = $this->validateRelatedModels($data, [
            'country_id' => Country::class,
        ]);

        if ($relationshipError) {
            return $relationshipError;
        }

        $city = $this->executeInTransaction(function () use ($data) {
            $city = City::create($data);
            $this->logApiOperation('create', $city, $data);

            return $city;
        });

        if ($city instanceof JsonResponse) {
            return $city;
        }

        return (new CityResource($city->load(['country', 'locations'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(City $city): CityResource
    {
        $this->authorize('view', $city);

        return new CityResource($city->load(['country', 'locations']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCityRequest $request, City $city)
    {
        $this->authorize('update', $city);

        $data = $request->validated();

        // Check for concurrent modifications
        $concurrencyError = $this->checkConcurrentModification($city, $request->header('If-Unmodified-Since'));
        if ($concurrencyError) {
            return $concurrencyError;
        }

        // Validate related models exist
        $relationshipError = $this->validateRelatedModels($data, [
            'country_id' => Country::class,
        ]);

        if ($relationshipError) {
            return $relationshipError;
        }

        $updatedCity = $this->executeInTransaction(function () use ($city, $data) {
            $city->update($data);
            $this->logApiOperation('update', $city, $data);

            return $city;
        });

        if ($updatedCity instanceof JsonResponse) {
            return $updatedCity;
        }

        return new CityResource($updatedCity->load(['country', 'locations']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(City $city)
    {
        $this->authorize('delete', $city);

        // Validate that the city can be deleted
        $deletionError = $this->validateDeletion($city);
        if ($deletionError) {
            return $deletionError;
        }

        $result = $this->executeInTransaction(function () use ($city) {
            $this->logApiOperation('delete', $city);
            $city->delete();

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
        return ['locations'];
    }
}
