<?php

namespace App\Filament\Resources\Treatments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TreatmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('venue.name')
                    ->searchable(),
                TextColumn::make('external_id')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('duration')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('min_duration')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('max_duration')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('price')
                    ->money()
                    ->sortable(),
                TextColumn::make('min_price')
                    ->money()
                    ->sortable(),
                TextColumn::make('max_price')
                    ->money()
                    ->sortable(),
                TextColumn::make('category')
                    ->searchable(),
                TextColumn::make('category_id')
                    ->searchable(),
                TextColumn::make('category_name')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100, 250, 500])
            ->defaultPaginationPageOption(50)
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active Treatments')
                    ->placeholder('All treatments')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
                SelectFilter::make('venue_id')
                    ->relationship('venue', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Venue'),
                SelectFilter::make('category')
                    ->options(function () {
                        return \App\Models\Treatment::distinct()->pluck('category', 'category')->filter()->toArray();
                    })
                    ->searchable()
                    ->label('Category'),
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
