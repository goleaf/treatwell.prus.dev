<?php

namespace App\Filament\Widgets;

use App\Models\City;
use App\Models\Rating;
use App\Models\Treatment;
use App\Models\Venue;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $averageRating = Venue::where('rating', '>', 0)->avg('rating') ?? 0;
        $activeTreatments = Treatment::where('is_active', true)->count();
        $verifiedRatings = Rating::where('is_verified', true)->count();
        $citiesWithVenues = City::whereHas('venues')->count();

        return [
            Stat::make('Total Venues', Venue::count())
                ->description('All venues in the system')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('primary'),
            Stat::make('Active Venues', Venue::where('is_active', true)->count())
                ->description(number_format((Venue::where('is_active', true)->count() / max(Venue::count(), 1)) * 100, 1).'% of total venues')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Average Rating', number_format($averageRating, 2))
                ->description('Across all venues with ratings')
                ->descriptionIcon('heroicon-m-star')
                ->color('warning'),
            Stat::make('Total Cities', City::count())
                ->description($citiesWithVenues.' cities with venues')
                ->descriptionIcon('heroicon-m-map-pin')
                ->color('info'),
            Stat::make('Total Treatments', Treatment::count())
                ->description($activeTreatments.' active treatments')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('purple'),
            Stat::make('Total Ratings', Rating::count())
                ->description($verifiedRatings.' verified ratings')
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color('gray'),
        ];
    }
}
