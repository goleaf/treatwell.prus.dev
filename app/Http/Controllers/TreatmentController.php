<?php

namespace App\Http\Controllers;

use App\Http\Requests\TreatmentRequest;
use App\Models\Treatment;
use App\Models\Venue;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class TreatmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $query = Treatment::query()
            ->with(['venue.city'])
            ->where('is_active', true);

        // Filter by venue if provided
        if ($request->filled('venue')) {
            $query->whereHas('venue', function ($q) use ($request) {
                $q->where('slug', $request->venue);
            });
        }

        // Filter by category if provided
        if ($request->filled('category')) {
            $query->where('category_name', $request->category);
        }

        // Search by name if provided
        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        // Filter by price range
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        $treatments = $query->paginate(12);
        $categories = Treatment::where('is_active', true)
            ->distinct()
            ->pluck('category_name')
            ->filter()
            ->sort();

        return view('treatments.index', compact('treatments', 'categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $venues = Venue::where('is_active', true)->orderBy('name')->get();
        $categories = Treatment::distinct()
            ->pluck('category_name')
            ->filter()
            ->sort();

        return view('treatments.create', compact('venues', 'categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TreatmentRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();

            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $treatment = DB::transaction(function () use ($data) {
                return Treatment::create($data);
            });

            Log::info('Treatment created successfully', [
                'treatment_id' => $treatment->id,
                'name' => $treatment->name,
                'venue_id' => $treatment->venue_id,
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->route('web.treatments.show', $treatment)
                ->with('success', 'Treatment created successfully.');

        } catch (QueryException $e) {
            Log::error('Database error creating treatment', [
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
            Log::error('Unexpected error creating treatment', [
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
     * Display the specified resource.
     */
    public function show(Treatment $treatment): View
    {
        $treatment->load(['venue.city', 'venue.ratingDetails']);

        return view('treatments.show', compact('treatment'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Treatment $treatment): View
    {
        $venues = Venue::where('is_active', true)->orderBy('name')->get();
        $categories = Treatment::distinct()
            ->pluck('category_name')
            ->filter()
            ->sort();

        return view('treatments.edit', compact('treatment', 'venues', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TreatmentRequest $request, Treatment $treatment): RedirectResponse
    {
        try {
            $data = $request->validated();

            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            DB::transaction(function () use ($treatment, $data) {
                $treatment->update($data);
            });

            Log::info('Treatment updated successfully', [
                'treatment_id' => $treatment->id,
                'name' => $treatment->name,
                'venue_id' => $treatment->venue_id,
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->route('web.treatments.show', $treatment)
                ->with('success', 'Treatment updated successfully.');

        } catch (QueryException $e) {
            Log::error('Database error updating treatment', [
                'treatment_id' => $treatment->id,
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
            Log::error('Unexpected error updating treatment', [
                'treatment_id' => $treatment->id,
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
    public function destroy(Treatment $treatment): RedirectResponse
    {
        try {
            DB::transaction(function () use ($treatment) {
                $treatment->delete();
            });

            Log::info('Treatment deleted successfully', [
                'treatment_id' => $treatment->id,
                'name' => $treatment->name,
                'venue_id' => $treatment->venue_id,
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->route('web.treatments.index')
                ->with('success', 'Treatment deleted successfully.');

        } catch (QueryException $e) {
            Log::error('Database error deleting treatment', [
                'treatment_id' => $treatment->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            $errorMessage = $this->getDatabaseErrorMessage($e);
            
            return redirect()
                ->back()
                ->withErrors(['error' => $errorMessage]);

        } catch (Throwable $e) {
            Log::error('Unexpected error deleting treatment', [
                'treatment_id' => $treatment->id,
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
                ? 'A treatment with this information already exists.' 
                : 'This operation violates a database constraint.',
            1451 => 'Cannot delete this treatment because it is referenced by other data.',
            1452 => 'The referenced record does not exist.',
            1048 => 'Required information is missing.',
            default => app()->environment('local', 'testing') 
                ? $e->getMessage() 
                : 'A database error occurred. Please try again.',
        };
    }
}
