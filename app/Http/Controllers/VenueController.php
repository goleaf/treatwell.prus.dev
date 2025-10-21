<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Procedure;
use App\Models\Venue;
use App\Services\VenueService;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    protected VenueService $venueService;

    public function __construct(VenueService $venueService)
    {
        $this->venueService = $venueService;
    }

    /**
     * Display a listing of the venues.
     */
    public function index(Request $request)
    {
        $query = Venue::query()
            ->with([
                'location.city',
                'rating',
                'procedures',
                'treatments' => fn ($builder) => $builder->orderBy('min_price'),
                'images',
            ])
            ->withMin('treatments', 'min_price')
            ->withMax('treatments', 'max_price');

        $search = $request->string('search')->toString();
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type_name', $request->input('type'));
        }

        $cities = City::orderBy('name')->get();
        $procedures = Procedure::orderBy('name')->get();

        if ($request->filled('city')) {
            $cityValue = $request->input('city');
            $selectedCity = $cities->firstWhere('slug', $cityValue)
                ?? $cities->firstWhere('id', (int) $cityValue);

            if ($selectedCity) {
                $query->where(function ($builder) use ($selectedCity) {
                    $builder->whereHas('location.city', function ($cityQuery) use ($selectedCity) {
                        $cityQuery->where('cities.id', $selectedCity->id);
                    })->orWhereHas('cities', function ($cityQuery) use ($selectedCity) {
                        $cityQuery->where('cities.id', $selectedCity->id);
                    });
                });
            }
        }

        if ($request->filled('procedure')) {
            $procedureValue = $request->input('procedure');
            $procedure = $procedures->firstWhere('slug', $procedureValue)
                ?? $procedures->firstWhere('id', (int) $procedureValue);

            if ($procedure) {
                $query->whereHas('procedures', function ($procedureQuery) use ($procedure) {
                    $procedureQuery->where('procedures.id', $procedure->id);
                });
            }
        }

        if ($request->filled('rating')) {
            $ratingThreshold = (float) $request->input('rating');
            $query->whereHas('rating', function ($ratingQuery) use ($ratingThreshold) {
                $ratingQuery->where('weighted_average', '>=', $ratingThreshold);
            });
        }

        if ($request->boolean('new')) {
            $query->where('is_new_venue', true);
        }

        $minPrice = $request->input('min_price');
        if ($minPrice !== null && $minPrice !== '') {
            $price = (float) $minPrice;
            $query->whereHas('treatments', function ($treatmentQuery) use ($price) {
                $treatmentQuery->where(function ($filter) use ($price) {
                    $filter->whereNotNull('min_price')->where('min_price', '>=', $price)
                        ->orWhere(function ($fallback) use ($price) {
                            $fallback->whereNull('min_price')->where('max_price', '>=', $price);
                        });
                });
            });
        }

        $maxPrice = $request->input('max_price');
        if ($maxPrice !== null && $maxPrice !== '') {
            $price = (float) $maxPrice;
            $query->whereHas('treatments', function ($treatmentQuery) use ($price) {
                $treatmentQuery->where(function ($filter) use ($price) {
                    $filter->whereNotNull('max_price')->where('max_price', '<=', $price)
                        ->orWhere(function ($fallback) use ($price) {
                            $fallback->whereNull('max_price')->where('min_price', '<=', $price);
                        });
                });
            });
        }

        $sort = $request->input('sort', 'name_asc');
        switch ($sort) {
            case 'name_desc':
                $query->orderByDesc('name');
                break;
            case 'rating_desc':
                $query->orderByDesc(function ($subQuery) {
                    $subQuery->select('weighted_average')
                        ->from('ratings')
                        ->whereColumn('ratings.venue_id', 'venues.id')
                        ->limit(1);
                })->orderBy('name');
                break;
            case 'rating_asc':
                $query->orderBy(function ($subQuery) {
                    $subQuery->select('weighted_average')
                        ->from('ratings')
                        ->whereColumn('ratings.venue_id', 'venues.id')
                        ->limit(1);
                })->orderBy('name');
                break;
            case 'price_low_high':
                $query->orderByRaw('COALESCE(treatments_min_price, treatments_max_price, 999999) asc')->orderBy('name');
                break;
            case 'price_high_low':
                $query->orderByRaw('COALESCE(treatments_max_price, treatments_min_price, 0) desc')->orderBy('name');
                break;
            case 'newest':
                $query->orderByDesc('created_at');
                break;
            case 'oldest':
                $query->orderBy('created_at');
                break;
            default:
                $query->orderBy('name');
                break;
        }

        $perPage = (int) $request->input('per_page', 18);
        $perPage = max(6, min($perPage, 60));

        $venues = $query->paginate($perPage)->withQueryString();

        $types = Venue::select('type_name')
            ->whereNotNull('type_name')
            ->distinct()
            ->orderBy('type_name')
            ->pluck('type_name');

        return view('venues.index', compact('venues', 'types', 'cities', 'procedures'));
    }

    /**
     * Display the specified venue by slug.
     */
    public function showBySlug(string $slug)
    {
        $venue = Venue::with([
            'location.city',
            'rating',
            'images',
            'openingHours',
            'treatments',
            'procedures',
            'cities',
        ])->where('normalised_name', $slug)
            ->orWhere('slug', $slug)
            ->firstOrFail();

        return view('venues.show', compact('venue'));
    }

    /**
     * Display venues by city.
     */
    public function byCity(City $city, Request $request)
    {
        $query = Venue::with(['location.city', 'rating', 'images' => function ($query) {
            $query->where('is_primary', true);
        }]);

        if ($city->is_main_city && ($request->include_subregions ?? true)) {
            // Include main city and all its subregions
            $cityIds = $city->subregions()->pluck('id')->push($city->id);
            $query->whereHas('location', function ($q) use ($cityIds) {
                $q->whereIn('city_id', $cityIds);
            });
        } else {
            // Just this specific city
            $query->whereHas('location', function ($q) use ($city) {
                $q->where('city_id', $city->id);
            });
        }

        $venues = $query
            ->when($request->subregion, function ($query, $subregion) {
                $query->whereHas('location.city', function ($q) use ($subregion) {
                    $q->where('subregion', $subregion);
                });
            })
            ->when($request->rating, function ($query, $rating) {
                $query->whereHas('rating', function ($q) use ($rating) {
                    $q->where('weighted_average', '>=', $rating);
                });
            })
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->type, function ($query, $type) {
                $query->where('type_name', $type);
            })
            ->when($request->sort, function ($query, $sort) {
                switch ($sort) {
                    case 'name':
                        $query->orderBy('name', 'asc');
                        break;
                    case 'name_desc':
                        $query->orderBy('name', 'desc');
                        break;
                    case 'rating':
                        $query->whereHas('rating', function ($q) {
                            $q->orderBy('weighted_average', 'desc');
                        });
                        break;
                    case 'rating_asc':
                        $query->whereHas('rating', function ($q) {
                            $q->orderBy('weighted_average', 'asc');
                        });
                        break;
                    case 'newest':
                        $query->orderBy('created_at', 'desc');
                        break;
                    case 'oldest':
                        $query->orderBy('created_at', 'asc');
                        break;
                    default:
                        $query->orderBy('name', 'asc');
                }
            }, function ($query) {
                $query->orderBy('name', 'asc');
            })
            ->paginate(20)
            ->withQueryString();

        // Get main cities for the dropdown
        $mainCities = City::where('is_main_city', true)->orderBy('name')->get();

        // Get all cities
        $cities = City::orderBy('name')->get();

        // Get unique subregions for this city
        $subregions = null;
        if ($city->is_main_city) {
            $subregions = City::where('main_city_id', $city->id)->distinct()->pluck('subregion')->filter()->sort();
        }

        return view('venues.index', compact('venues', 'cities', 'mainCities', 'city', 'subregions'));
    }

    /**
     * Display the dashboard with statistics.
     */
    public function dashboard()
    {
        $totalVenues = Venue::count();
        $totalCities = City::count();

        $topRatedVenues = Venue::whereHas('rating', function ($query) {
            $query->orderBy('weighted_average', 'desc');
        })
            ->with(['location.city', 'rating'])
            ->take(10)
            ->get();

        $citiesWithMostVenues = City::withMostVenues(10)->get();

        return view('dashboard', compact(
            'totalVenues',
            'totalCities',
            'topRatedVenues',
            'citiesWithMostVenues'
        ));
    }
}
