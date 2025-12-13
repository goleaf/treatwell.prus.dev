<?php

namespace App\Http\Controllers;

use App\Http\Requests\Store{{ModelName}}Request;
use App\Http\Requests\Update{{ModelName}}Request;
use App\Models\{{ModelName}};
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class {{ModelName}}Controller extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        ${{modelNamePlural}} = {{ModelName}}::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(15);

        return view('{{modelNamePlural}}.index', compact('{{modelNamePlural}}'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('{{modelNamePlural}}.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Store{{ModelName}}Request $request): RedirectResponse
    {
        ${{modelName}} = {{ModelName}}::create($request->validated());

        return redirect()
            ->route('{{modelNamePlural}}.show', ${{modelName}})
            ->with('success', '{{ModelName}} created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show({{ModelName}} ${{modelName}}): View
    {
        return view('{{modelNamePlural}}.show', compact('{{modelName}}'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit({{ModelName}} ${{modelName}}): View
    {
        return view('{{modelNamePlural}}.edit', compact('{{modelName}}'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Update{{ModelName}}Request $request, {{ModelName}} ${{modelName}}): RedirectResponse
    {
        ${{modelName}}->update($request->validated());

        return redirect()
            ->route('{{modelNamePlural}}.show', ${{modelName}})
            ->with('success', '{{ModelName}} updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy({{ModelName}} ${{modelName}}): RedirectResponse
    {
        ${{modelName}}->delete();

        return redirect()
            ->route('{{modelNamePlural}}.index')
            ->with('success', '{{ModelName}} deleted successfully.');
    }
}