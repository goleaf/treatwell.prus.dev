<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreOpeningHourRequest;
use App\Http\Requests\Api\UpdateOpeningHourRequest;
use App\Http\Resources\OpeningHourResource;
use App\Models\OpeningHour;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OpeningHourController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = OpeningHour::query();

        $query->with(['venue']);

        // Filter by venue
        if ($request->has('venue_id') && $request->input('venue_id')) {
            $venueId = $request->input('venue_id');
            $query->where('venue_id', $venueId);
        }

        // Filter by day of week
        if ($request->has('day_of_week') && $request->input('day_of_week')) {
            $dayOfWeek = $request->input('day_of_week');
            $query->where('day_of_week', $dayOfWeek);
        }

        // Filter by open status
        if ($request->has('is_open') && $request->input('is_open') !== null) {
            $isOpen = filter_var($request->input('is_open'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_open', $isOpen);
        }

        // Sorting
        $sort = $request->input('sort', 'day_order');
        switch ($sort) {
            case 'day_order':
                // Order by day of week (Monday first)
                $query->orderByRaw("FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')");
                break;
            case 'opening_time':
                $query->orderBy('opening_time', 'asc');
                break;
            case 'closing_time':
                $query->orderBy('closing_time', 'asc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            default:
                $query->orderByRaw("FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')");
        }

        $perPage = $request->input('per_page', 15);
        $openingHours = $query->paginate($perPage);

        return OpeningHourResource::collection($openingHours);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOpeningHourRequest $request)
    {
        $openingHour = OpeningHour::create($request->validated());

        return (new OpeningHourResource($openingHour->load(['venue'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(OpeningHour $openingHour): OpeningHourResource
    {
        return new OpeningHourResource($openingHour->load(['venue']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOpeningHourRequest $request, OpeningHour $openingHour): OpeningHourResource
    {
        $openingHour->update($request->validated());

        return new OpeningHourResource($openingHour->load(['venue']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OpeningHour $openingHour): Response
    {
        $openingHour->delete();

        return response()->noContent();
    }

    /**
     * Get opening hours for a specific venue.
     */
    public function byVenue(Request $request, $venueId)
    {
        $request->validate([
            'venue_id' => 'exists:venues,id',
        ]);

        $openingHours = OpeningHour::where('venue_id', $venueId)
            ->with(['venue'])
            ->orderByRaw("FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')")
            ->get();

        return OpeningHourResource::collection($openingHours);
    }

    /**
     * Get opening hours for today.
     */
    public function today()
    {
        $today = now()->format('l'); // Full day name (Monday, Tuesday, etc.)

        $openingHours = OpeningHour::forDay($today)
            ->with(['venue'])
            ->get();

        return OpeningHourResource::collection($openingHours);
    }

    /**
     * Get venues that are currently open.
     */
    public function currentlyOpen()
    {
        $today = now()->format('l');
        $currentTime = now()->format('H:i');

        $openingHours = OpeningHour::where('day_of_week', $today)
            ->where('is_open', true)
            ->whereTime('opening_time', '<=', $currentTime)
            ->whereTime('closing_time', '>=', $currentTime)
            ->with(['venue'])
            ->get();

        return OpeningHourResource::collection($openingHours);
    }
}
