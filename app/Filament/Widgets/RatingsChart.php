<?php

namespace App\Filament\Widgets;

use App\Models\Rating;
use Filament\Widgets\ChartWidget;

class RatingsChart extends ChartWidget
{
    protected ?string $heading = 'Ratings Distribution';

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $data = Rating::whereNotNull('rating')
            ->get()
            ->groupBy('rating')
            ->map(function ($group) {
                return $group->count();
            });

        // Fill in missing ratings with 0
        $ratings = [1, 2, 3, 4, 5];
        foreach ($ratings as $rating) {
            if (! $data->has($rating)) {
                $data->put($rating, 0);
            }
        }

        $data = $data->sortKeys();

        return [
            'datasets' => [
                [
                    'label' => 'Number of Ratings',
                    'data' => $data->values()->toArray(),
                    'backgroundColor' => [
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(249, 115, 22, 0.8)',
                        'rgba(251, 191, 36, 0.8)',
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                    ],
                    'borderColor' => [
                        'rgba(239, 68, 68, 1)',
                        'rgba(249, 115, 22, 1)',
                        'rgba(251, 191, 36, 1)',
                        'rgba(34, 197, 94, 1)',
                        'rgba(16, 185, 129, 1)',
                    ],
                ],
            ],
            'labels' => $data->keys()->map(fn ($rating) => $rating.' Star'.($rating != 1 ? 's' : ''))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
