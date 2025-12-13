<?php

namespace App\Filament\Pages;

use App\Models\City;
use App\Models\Rating;
use App\Models\Treatment;
use App\Models\Venue;
use BackedEnum;
use Filament\Pages\Page;

class Stats extends Page
{
    protected string $view = 'filament.pages.stats';

    public function mount(): void
    {
        //
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Statistics';

    protected static ?int $navigationSort = 2;

    public function getStats(): array
    {
        $totalVenues = Venue::count();
        $activeVenues = Venue::where('is_active', true)->count();
        $totalCities = City::count();
        $citiesWithVenues = City::whereHas('venues')->count();
        $totalTreatments = Treatment::count();
        $activeTreatments = Treatment::where('is_active', true)->count();
        $totalRatings = Rating::count();
        $verifiedRatings = Rating::where('is_verified', true)->count();
        $averageRating = Venue::where('rating', '>', 0)->avg('rating') ?? 0;
        $averageRatingAll = Rating::whereNotNull('rating')->avg('rating') ?? 0;
        $totalVenueRatings = Venue::where('rating_count', '>', 0)->sum('rating_count');
        $topRatedVenue = Venue::where('rating', '>', 0)->orderByDesc('rating')->first();
        $mostRatingsVenue = Venue::where('rating_count', '>', 0)->orderByDesc('rating_count')->first();

        return [
            'total_venues' => $totalVenues,
            'active_venues' => $activeVenues,
            'inactive_venues' => $totalVenues - $activeVenues,
            'active_venues_percentage' => $totalVenues > 0 ? round(($activeVenues / $totalVenues) * 100, 1) : 0,
            'total_cities' => $totalCities,
            'cities_with_venues' => $citiesWithVenues,
            'cities_without_venues' => $totalCities - $citiesWithVenues,
            'total_treatments' => $totalTreatments,
            'active_treatments' => $activeTreatments,
            'inactive_treatments' => $totalTreatments - $activeTreatments,
            'total_ratings' => $totalRatings,
            'verified_ratings' => $verifiedRatings,
            'unverified_ratings' => $totalRatings - $verifiedRatings,
            'average_rating' => round($averageRating, 2),
            'average_rating_all' => round($averageRatingAll, 2),
            'total_venue_ratings' => $totalVenueRatings,
            'top_rated_venue' => $topRatedVenue ? [
                'name' => $topRatedVenue->name,
                'rating' => $topRatedVenue->rating,
            ] : null,
            'most_ratings_venue' => $mostRatingsVenue ? [
                'name' => $mostRatingsVenue->name,
                'count' => $mostRatingsVenue->rating_count,
            ] : null,
        ];
    }
}
