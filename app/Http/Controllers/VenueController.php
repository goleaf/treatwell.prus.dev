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
{
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
        $cities = City::orderBy('name')->get();

        return view('venues.create', compact('cities'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(VenueRequest $request): RedirectResponse
    {
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
        $cities = City::orderBy('name')->get();

        return view('venues.edit', compact('venue', 'cities'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(VenueRequest $request, Venue $venue): RedirectResponse
    {
        try {
            $data = $request->validated();

            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            DB::transaction(function () use ($venue, $data) {
                $venue->update($data);
            });

            Log::info('Venue updated successfully', [
                'venue_id' => $venue->id,
                'name' => $venue->name,
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->route('web.venues.show', $venue)
                ->with('success', 'Venue updated successfully.');

        } catch (QueryException $e) {
            Log::error('Database error updating venue', [
                'venue_id' => $venue->id,
                'error' => $e->getMessage(),
                'data' => $data ?? [],
                'user_id' => auth()->id(),
            ]);

            $errorMessage = $this->getDatabaseErrorMessage($e);
            
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => $errorMessage]);

        } catch (Throwable $e) {
            Log::error('Unexpected error updating venue', [
                'venue_id' => $venue->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'An unexpected error occurred. Please try again.']);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Venue $venue): RedirectResponse
    {
        try {
            // Check if venue has related data that would prevent deletion
            $hasRelatedData = $venue->treatments()->exists() || 
                             $venue->images()->exists() || 
                             $venue->openingHours()->exists();

            if ($hasRelatedData) {
                return redirect()
                    ->back()
                    ->withErrors(['error' => 'Cannot delete this venue because it has associated treatments, images, or opening hours. Please remove them first.']);
            }

            DB::transaction(function () use ($venue) {
                $venue->delete();
            });

            Log::info('Venue deleted successfully', [
                'venue_id' => $venue->id,
                'name' => $venue->name,
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->route('web.venues.index')
                ->with('success', 'Venue deleted successfully.');

        } catch (QueryException $e) {
            Log::error('Database error deleting venue', [
                'venue_id' => $venue->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            $errorMessage = $this->getDatabaseErrorMessage($e);
            
            return redirect()
                ->back()
                ->withErrors(['error' => $errorMessage]);

        } catch (Throwable $e) {
            Log::error('Unexpected error deleting venue', [
                'venue_id' => $venue->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->back()
                ->withErrors(['error' => 'An unexpected error occurred. Please try again.']);
        }
    }

    /**
     * Get user-friendly database error messages.
     */
    private function getDatabaseErrorMessage(QueryException $e): string
    {
        $errorCode = $e->errorInfo[1] ?? $e->getCode();

        return match ($errorCode) {
            1062, 23000 => str_contains($e->getMessage(), 'Duplicate entry') 
                ? 'A venue with this information already exists.' 
                : 'This operation violates a database constraint.',
            1451 => 'Cannot delete this venue because it is referenced by other data.',
            1452 => 'The referenced record does not exist.',
            1048 => 'Required information is missing.',
            default => app()->environment('local', 'testing') 
                ? $e->getMessage() 
                : 'A database error occurred. Please try again.',
        };
    }
}
