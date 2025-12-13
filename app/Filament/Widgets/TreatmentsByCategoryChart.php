<?php

namespace App\Filament\Widgets;

use App\Models\Treatment;
use Filament\Widgets\ChartWidget;

class TreatmentsByCategoryChart extends ChartWidget
{
    protected ?string $heading = 'Treatments by Category';

    protected static ?int $sort = 5;

    protected function getData(): array
    {
        $data = Treatment::whereNotNull('category')
            ->where('category', '!=', '')
            ->get()
            ->groupBy('category')
            ->map(function ($group) {
                return $group->count();
            })
            ->sortDesc()
            ->take(10);

        return [
            'datasets' => [
                [
                    'label' => 'Number of Treatments',
                    'data' => $data->values()->toArray(),
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.5)',
                        'rgba(147, 51, 234, 0.5)',
                        'rgba(236, 72, 153, 0.5)',
                        'rgba(251, 191, 36, 0.5)',
                        'rgba(34, 197, 94, 0.5)',
                        'rgba(249, 115, 22, 0.5)',
                        'rgba(239, 68, 68, 0.5)',
                        'rgba(168, 85, 247, 0.5)',
                        'rgba(99, 102, 241, 0.5)',
                        'rgba(14, 165, 233, 0.5)',
                    ],
                    'borderColor' => [
                        'rgba(59, 130, 246, 1)',
                        'rgba(147, 51, 234, 1)',
                        'rgba(236, 72, 153, 1)',
                        'rgba(251, 191, 36, 1)',
                        'rgba(34, 197, 94, 1)',
                        'rgba(249, 115, 22, 1)',
                        'rgba(239, 68, 68, 1)',
                        'rgba(168, 85, 247, 1)',
                        'rgba(99, 102, 241, 1)',
                        'rgba(14, 165, 233, 1)',
                    ],
                ],
            ],
            'labels' => $data->keys()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
