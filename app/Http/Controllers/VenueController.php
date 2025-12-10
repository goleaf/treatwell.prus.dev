<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesWebErrors;
use App\Http\Requests\VenueRequest;
use App\Models\City;
use App\Models\Venue;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class VenueController extends Controller
{
    use HandlesWebErrors;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $query = Venue::query()
            ->with(['city', 'ratingDetails'])
            ->where('is_active', true);

        // Filter by city if provided
        if ($request->filled('city')) {
            $query->whereHas('city', function ($q) use ($request) {
                $q->where('slug', $request->city);
            });
        }

        // Search by name if provided
        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $venues = $query->paginate(12);
        $cities = City::orderBy('name')->get();

        return view('venues.index', compact('venues', 'cities'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $this->authorize('create', Venue::class);

        $cities = City::orderBy('name')->get();

        return view('venues.create', compact('cities'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(VenueRequest $request): RedirectResponse
    {
        $this->authorize('create', Venue::class);

        try {
            $data = $request->validated();

            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $venue = $this->executeWebTransaction(function () use ($data) {
                return Venue::create($data);
            }, 'venue creation');

            $this->logWebOperation('create', $venue, $data);

            return redirect()
                ->route('web.venues.show', $venue)
                ->with('success', 'Venue created successfully.');

        } catch (QueryException $e) {
            return $this->handleWebDatabaseError($e, 'venue');
        } catch (Throwable $e) {
            return $this->handleWebUnexpectedError($e, 'venue creation');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Venue $venue): View
    {
        $venue->load([
            'city',
            'treatments' => function ($query) {
                $query->where('is_active', true);
            },
            'ratingDetails',
            'images',
            'openingHours',
        ]);

        return view('venues.show', compact('venue'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Venue $venue): View
    {
        $this->authorize('update', $venue);

        $cities = City::orderBy('name')->get();

        return view('venues.edit', compact('venue', 'cities'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(VenueRequest $request, Venue $venue): RedirectResponse
    {
        $this->authorize('update', $venue);

        try {
            $data = $request->validated();

            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $this->executeWebTransaction(function () use ($venue, $data) {
                $venue->update($data);
            }, 'venue update');

            $this->logWebOperation('update', $venue, $data);

            return redirect()
                ->route('web.venues.show', $venue)
                ->with('success', 'Venue updated successfully.');

        } catch (QueryException $e) {
            return $this->handleWebDatabaseError($e, 'venue');
        } catch (Throwable $e) {
            return $this->handleWebUnexpectedError($e, 'venue update');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Venue $venue): RedirectResponse
    {
        $this->authorize('delete', $venue);

        try {
            // Validate that the venue can be deleted
            $validationError = $this->validateWebDeletion($venue, ['treatments', 'images', 'openingHours']);
            if ($validationError) {
                return $validationError;
            }

            $this->executeWebTransaction(function () use ($venue) {
                $venue->delete();
            }, 'venue deletion');

            $this->logWebOperation('delete', $venue);

            return redirect()
                ->route('web.venues.index')
                ->with('success', 'Venue deleted successfully.');

        } catch (QueryException $e) {
            return $this->handleWebDatabaseError($e, 'venue');
        } catch (Throwable $e) {
            return $this->handleWebUnexpectedError($e, 'venue deletion');
        }
    }
}
