<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\HandlesApiErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreServiceRequest;
use App\Http\Requests\Api\UpdateServiceRequest;
use App\Http\Resources\ServiceCollection;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    use AuthorizesRequests, HandlesApiErrors;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Service::class);
        $query = Service::query();

        $query->with(['venue', 'images']);

        // Search
        if ($request->has('search') && $request->input('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        }

        // Filter by venue
        if ($request->has('venue_id') && $request->input('venue_id')) {
            $venueId = $request->input('venue_id');
            $query->where('venue_id', $venueId);
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

        // Filter by featured status
        if ($request->has('featured') && $request->input('featured') !== null) {
            $featured = filter_var($request->input('featured'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_featured', $featured);
        }

        // Filter by price range
        if ($request->has('min_price') && $request->input('min_price')) {
            $minPrice = $request->input('min_price');
            $query->where(function ($q) use ($minPrice) {
                $q->where('price', '>=', $minPrice)
                    ->orWhere('min_price', '>=', $minPrice);
            });
        }

        if ($request->has('max_price') && $request->input('max_price')) {
            $maxPrice = $request->input('max_price');
            $query->where(function ($q) use ($maxPrice) {
                $q->where('price', '<=', $maxPrice)
                    ->orWhere('max_price', '<=', $maxPrice);
            });
        }

        // Sorting
        $sort = $request->input('sort', 'sort_order');
        switch ($sort) {
            case 'name':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'price':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'duration':
                $query->orderBy('duration', 'asc');
                break;
            case 'duration_desc':
                $query->orderBy('duration', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'sort_order':
                $query->orderBy('sort_order', 'asc');
                break;
            default:
                $query->orderBy('sort_order', 'asc');
        }

        $perPage = $request->input('per_page', 15);
        $services = $query->paginate($perPage);

        return new ServiceCollection($services);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreServiceRequest $request)
    {
        $this->authorize('create', Service::class);

        $data = $request->validated();

        // Validate related models exist
        $relationshipError = $this->validateRelatedModels($data, [
            'venue_id' => Venue::class,
        ]);

        if ($relationshipError) {
            return $relationshipError;
        }

        $service = $this->executeInTransaction(function () use ($data) {
            $service = Service::create($data);
            $this->logApiOperation('create', $service, $data);

            return $service;
        });

        if ($service instanceof JsonResponse) {
            return $service;
        }

        return (new ServiceResource($service->load(['venue', 'images'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Service $service): ServiceResource
    {
        $this->authorize('view', $service);

        return new ServiceResource($service->load(['venue', 'images']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateServiceRequest $request, Service $service)
    {
        $this->authorize('update', $service);

        $data = $request->validated();

        // Check for concurrent modifications
        $concurrencyError = $this->checkConcurrentModification($service, $request->header('If-Unmodified-Since'));
        if ($concurrencyError) {
            return $concurrencyError;
        }

        // Validate related models exist
        $relationshipError = $this->validateRelatedModels($data, [
            'venue_id' => Venue::class,
        ]);

        if ($relationshipError) {
            return $relationshipError;
        }

        $updatedService = $this->executeInTransaction(function () use ($service, $data) {
            $service->update($data);
            $this->logApiOperation('update', $service, $data);

            return $service;
        });

        if ($updatedService instanceof JsonResponse) {
            return $updatedService;
        }

        return new ServiceResource($updatedService->load(['venue', 'images']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Service $service)
    {
        $this->authorize('delete', $service);

        // Validate that the service can be deleted
        $deletionError = $this->validateDeletion($service);
        if ($deletionError) {
            return $deletionError;
        }

        $result = $this->executeInTransaction(function () use ($service) {
            $this->logApiOperation('delete', $service);
            $service->delete();

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
        return ['images'];
    }

    /**
     * Get distinct service categories.
     */
    public function categories()
    {
        $categories = Service::select('category')
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return response()->json($categories);
    }

    /**
     * Get only active services.
     */
    public function active()
    {
        $services = Service::active()
            ->with(['venue', 'images'])
            ->orderBy('sort_order')
            ->get();

        return new ServiceCollection($services);
    }

    /**
     * Get only featured services.
     */
    public function featured()
    {
        $services = Service::featured()
            ->with(['venue', 'images'])
            ->orderBy('sort_order')
            ->get();

        return new ServiceCollection($services);
    }
}
