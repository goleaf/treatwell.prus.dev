<?php

namespace App\Http\Controllers;

use App\Services\CommandService;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * The command service
     */
    protected $commandService;

    /**
     * Create a new controller instance.
     */
    public function __construct(CommandService $commandService)
    {
        $this->commandService = $commandService;
    }

    /**
     * Display the admin dashboard.
     */
    public function index()
    {
        return view('admin.index');
    }

    /**
     * Handle the request to scrape a specific city.
     */
    public function scrapeCity(Request $request)
    {
        $validated = $request->validate([
            'city' => 'required|string',
            'page_limit' => 'nullable|integer|min:1',
        ]);

        $city = $validated['city'];
        $pageLimit = $validated['page_limit'] ?? null;

        // Run the command in the background
        $command = 'php '.base_path('artisan')." scrape:treatwell {$city}";

        if ($pageLimit) {
            $command .= " --limit={$pageLimit}";
        }

        $command .= ' > /dev/null 2>&1 &';

        $this->commandService->execute($command);

        return redirect()->route('admin.index')
            ->with('success', "Scraping started for city: {$city}. This process will run in the background.");
    }

    /**
     * Handle the request to scrape all cities.
     */
    public function scrapeAllCities(Request $request)
    {
        $validated = $request->validate([
            'page_limit' => 'nullable|integer|min:1',
        ]);

        $pageLimit = $validated['page_limit'] ?? null;

        // Run the command in the background
        $command = 'php '.base_path('artisan').' scrape:all-cities';

        if ($pageLimit) {
            $command .= " --limit-pages={$pageLimit}";
        }

        $command .= ' > /dev/null 2>&1 &';

        $this->commandService->execute($command);

        return redirect()->route('admin.index')
            ->with('success', 'Scraping started for all cities. This process will run in the background and may take a while to complete.');
    }
}
