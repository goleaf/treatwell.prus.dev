<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreRatingRequest;
use App\Http\Requests\Api\UpdateRatingRequest;
use App\Http\Resources\RatingCollection;
use App\Http\Resources\RatingResource;
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RatingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Rating::query();

        $query->with(['venue']);

        // Search
        if ($request->has('search') && $request->input('search')) {
            $search = $request->input('search');
            $query->where('reviewer_name', 'like', "%{$search}%")
                ->orWhere('comment', 'like', "%{$search}%");
        }

        // Filter by venue
        if ($request->has('venue_id') && $request->input('venue_id')) {
            $venueId = $request->input('venue_id');
            $query->where('venue_id', $venueId);
        }

        // Filter by verified status
        if ($request->has('verified') && $request->input('verified') !== null) {
            $verified = filter_var($request->input('verified'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_verified', $verified);
        }

        // Filter by rating range
        if ($request->has('min_rating') && $request->input('min_rating')) {
            $minRating = $request->input('min_rating');
            $query->where(function ($q) use ($minRating) {
                $q->where('rating', '>=', $minRating)
                    ->orWhere('weighted_average', '>=', $minRating)
                    ->orWhere('display_average', '>=', $minRating);
            });
        }

        if ($request->has('max_rating') && $request->input('max_rating')) {
            $maxRating = $request->input('max_rating');
            $query->where(function ($q) use ($maxRating) {
                $q->where('rating', '<=', $maxRating)
                    ->orWhere('weighted_average', '<=', $maxRating)
                    ->orWhere('display_average', '<=', $maxRating);
            });
        }

        // Sorting
        $sort = $request->input('sort', 'newest');
        switch ($sort) {
            case 'rating':
                $query->orderBy('rating', 'desc');
                break;
            case 'rating_asc':
                $query->orderBy('rating', 'asc');
                break;
            case 'weighted_average':
                $query->orderBy('weighted_average', 'desc');
                break;
            case 'weighted_average_asc':
                $query->orderBy('weighted_average', 'asc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        $perPage = $request->input('per_page', 15);
        $ratings = $query->paginate($perPage);

        return new RatingCollection($ratings);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRatingRequest $request)
    {
        $rating = Rating::create($request->validated());

        return (new RatingResource($rating->load(['venue'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Rating $rating): RatingResource
    {
        return new RatingResource($rating->load(['venue']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRatingRequest $request, Rating $rating): RatingResource
    {
        $rating->update($request->validated());

        return new RatingResource($rating->load(['venue']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Rating $rating): Response
    {
        $rating->delete();

        return response()->noContent();
    }

    /**
     * Get only verified ratings.
     */
    public function verified()
    {
        $ratings = Rating::verified()
            ->with(['venue'])
            ->orderBy('created_at', 'desc')
            ->get();

        return new RatingCollection($ratings);
    }

    /**
     * Get rating statistics.
     */
    public function stats()
    {
        $totalRatings = Rating::count();
        $verifiedRatings = Rating::verified()->count();
        $averageRating = Rating::avg('weighted_average');

        $ratingDistribution = Rating::selectRaw('
            FLOOR(COALESCE(rating, weighted_average, display_average)) as rating_floor,
            COUNT(*) as count
        ')
            ->groupBy('rating_floor')
            ->orderBy('rating_floor')
            ->get();

        return response()->json([
            'total_ratings' => $totalRatings,
            'verified_ratings' => $verifiedRatings,
            'average_rating' => round($averageRating, 2),
            'rating_distribution' => $ratingDistribution,
        ]);
    }
}
