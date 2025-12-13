<?php

namespace App\Filament\Resources\Ratings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class RatingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('venue.name')
                    ->searchable(),
                TextColumn::make('reviewer_name')
                    ->searchable(),
                TextColumn::make('reviewer_email')
                    ->searchable(),
                TextColumn::make('rating')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_verified')
                    ->boolean(),
                TextColumn::make('comment')
                    ->limit(50)
                    ->wrap()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('weighted_average')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('cleanliness_avg')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('cleanliness_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('staff_avg')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('staff_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('atmosphere_avg')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('atmosphere_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('display_average')
                    ->numeric()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100, 250, 500])
            ->defaultPaginationPageOption(50)
            ->filters([
                TernaryFilter::make('is_verified')
                    ->label('Verified Ratings')
                    ->placeholder('All ratings')
                    ->trueLabel('Verified only')
                    ->falseLabel('Unverified only'),
                SelectFilter::make('venue_id')
                    ->relationship('venue', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Venue'),
                SelectFilter::make('rating')
                    ->options([
                        1 => '1 Star',
                        2 => '2 Stars',
                        3 => '3 Stars',
                        4 => '4 Stars',
                        5 => '5 Stars',
                    ])
                    ->label('Rating'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
