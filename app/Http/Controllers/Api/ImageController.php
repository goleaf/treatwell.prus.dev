<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\HandlesApiErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreImageRequest;
use App\Http\Requests\Api\UpdateImageRequest;
use App\Http\Resources\ImageResource;
use App\Models\Image;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImageController extends Controller
{
    use AuthorizesRequests, HandlesApiErrors;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Image::class);
        $query = Image::query();

        $query->with(['venue', 'imageable']);

        // Filter by venue
        if ($request->has('venue_id') && $request->input('venue_id')) {
            $venueId = $request->input('venue_id');
            $query->where('venue_id', $venueId);
        }

        // Filter by imageable type
        if ($request->has('imageable_type') && $request->input('imageable_type')) {
            $imageableType = $request->input('imageable_type');
            $query->where('imageable_type', $imageableType);
        }

        // Filter by imageable id
        if ($request->has('imageable_id') && $request->input('imageable_id')) {
            $imageableId = $request->input('imageable_id');
            $query->where('imageable_id', $imageableId);
        }

        // Filter by primary status
        if ($request->has('is_primary') && $request->input('is_primary') !== null) {
            $isPrimary = filter_var($request->input('is_primary'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_primary', $isPrimary);
        }

        // Sorting
        $sort = $request->input('sort', 'sort_order');
        switch ($sort) {
            case 'sort_order':
                $query->ordered();
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            default:
                $query->ordered();
        }

        $perPage = $request->input('per_page', 15);
        $images = $query->paginate($perPage);

        return ImageResource::collection($images);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreImageRequest $request)
    {
        $this->authorize('create', Image::class);

        $data = $request->validated();

        // Validate related models exist
        $relationshipError = $this->validateRelatedModels($data, [
            'venue_id' => Venue::class,
        ]);

        if ($relationshipError) {
            return $relationshipError;
        }

        $image = $this->executeInTransaction(function () use ($data) {
            $image = Image::create($data);
            $this->logApiOperation('create', $image, $data);

            return $image;
        });

        if ($image instanceof JsonResponse) {
            return $image;
        }

        return (new ImageResource($image->load(['venue', 'imageable'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Image $image): ImageResource
    {
        $this->authorize('view', $image);

        return new ImageResource($image->load(['venue', 'imageable']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateImageRequest $request, Image $image)
    {
        $this->authorize('update', $image);

        $data = $request->validated();

        // Check for concurrent modifications
        $concurrencyError = $this->checkConcurrentModification($image, $request->header('If-Unmodified-Since'));
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

        $updatedImage = $this->executeInTransaction(function () use ($image, $data) {
            $image->update($data);
            $this->logApiOperation('update', $image, $data);

            return $image;
        });

        if ($updatedImage instanceof JsonResponse) {
            return $updatedImage;
        }

        return new ImageResource($updatedImage->load(['venue', 'imageable']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Image $image)
    {
        $this->authorize('delete', $image);

        $result = $this->executeInTransaction(function () use ($image) {
            $this->logApiOperation('delete', $image);
            $image->delete();

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
        // Images typically don't have critical relationships that prevent deletion
        return [];
    }

    /**
     * Get only primary images.
     */
    public function primary()
    {
        $images = Image::primary()
            ->with(['venue', 'imageable'])
            ->ordered()
            ->get();

        return ImageResource::collection($images);
    }

    /**
     * Get images for a specific model.
     */
    public function byModel(Request $request)
    {
        $request->validate([
            'imageable_type' => 'required|string',
            'imageable_id' => 'required|integer',
        ]);

        $images = Image::where('imageable_type', $request->imageable_type)
            ->where('imageable_id', $request->imageable_id)
            ->with(['venue', 'imageable'])
            ->ordered()
            ->get();

        return ImageResource::collection($images);
    }

    /**
     * Set image as primary for its model.
     */
    public function setPrimary(Image $image)
    {
        $updatedImage = $this->executeInTransaction(function () use ($image) {
            // Remove primary status from other images of the same model
            Image::where('imageable_type', $image->imageable_type)
                ->where('imageable_id', $image->imageable_id)
                ->update(['is_primary' => false]);

            // Set this image as primary
            $image->update(['is_primary' => true]);

            $this->logApiOperation('setPrimary', $image);

            return $image;
        });

        if ($updatedImage instanceof JsonResponse) {
            return $updatedImage;
        }

        return new ImageResource($updatedImage->load(['venue', 'imageable']));
    }

    /**
     * Reorder images for a specific model.
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'imageable_type' => 'required|string',
            'imageable_id' => 'required|integer',
            'image_ids' => 'required|array',
            'image_ids.*' => 'integer|exists:images,id',
        ]);

        $result = $this->executeInTransaction(function () use ($request) {
            foreach ($request->image_ids as $index => $imageId) {
                Image::where('id', $imageId)
                    ->where('imageable_type', $request->imageable_type)
                    ->where('imageable_id', $request->imageable_id)
                    ->update(['sort_order' => $index + 1]);
            }

            return true;
        });

        if ($result instanceof JsonResponse) {
            return $result;
        }

        return response()->json(['message' => 'Images reordered successfully']);
    }
}
