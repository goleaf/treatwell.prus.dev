<?php

namespace App\Filament\Widgets;

use App\Models\Venue;
use Filament\Widgets\ChartWidget;

class VenuesChart extends ChartWidget
{
    protected ?string $heading = 'Venues Created Over Time';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = Venue::where('created_at', '>=', now()->subDays(30))
            ->get()
            ->groupBy(function ($venue) {
                return $venue->created_at->format('Y-m-d');
            })
            ->map(function ($group) {
                return $group->count();
            })
            ->sortKeys();

        return [
            'datasets' => [
                [
                    'label' => 'Venues Created',
                    'data' => $data->values()->toArray(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgba(59, 130, 246, 1)',
                ],
            ],
            'labels' => $data->keys()->map(fn ($date) => \Carbon\Carbon::parse($date)->format('M d'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
