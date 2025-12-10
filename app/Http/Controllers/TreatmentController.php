<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesWebErrors;
use App\Http\Requests\TreatmentRequest;
use App\Models\Treatment;
use App\Models\Venue;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class TreatmentController extends Controller
{
    use HandlesWebErrors;

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
        $this->authorize('create', Treatment::class);

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
        $this->authorize('create', Treatment::class);

        try {
            $data = $request->validated();

            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $treatment = $this->executeWebTransaction(function () use ($data) {
                return Treatment::create($data);
            }, 'treatment creation');

            $this->logWebOperation('create', $treatment, $data);

            return redirect()
                ->route('web.treatments.show', $treatment)
                ->with('success', 'Treatment created successfully.');

        } catch (QueryException $e) {
            return $this->handleWebDatabaseError($e, 'treatment');
        } catch (Throwable $e) {
            return $this->handleWebUnexpectedError($e, 'treatment creation');
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
        $this->authorize('update', $treatment);

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
        $this->authorize('update', $treatment);

        try {
            $data = $request->validated();

            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $this->executeWebTransaction(function () use ($treatment, $data) {
                $treatment->update($data);
            }, 'treatment update');

            $this->logWebOperation('update', $treatment, $data);

            return redirect()
                ->route('web.treatments.show', $treatment)
                ->with('success', 'Treatment updated successfully.');

        } catch (QueryException $e) {
            return $this->handleWebDatabaseError($e, 'treatment');
        } catch (Throwable $e) {
            return $this->handleWebUnexpectedError($e, 'treatment update');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Treatment $treatment): RedirectResponse
    {
        $this->authorize('delete', $treatment);

        try {
            $this->executeWebTransaction(function () use ($treatment) {
                $treatment->delete();
            }, 'treatment deletion');

            $this->logWebOperation('delete', $treatment);

            return redirect()
                ->route('web.treatments.index')
                ->with('success', 'Treatment deleted successfully.');

        } catch (QueryException $e) {
            return $this->handleWebDatabaseError($e, 'treatment');
        } catch (Throwable $e) {
            return $this->handleWebUnexpectedError($e, 'treatment deletion');
        }
    }
}
