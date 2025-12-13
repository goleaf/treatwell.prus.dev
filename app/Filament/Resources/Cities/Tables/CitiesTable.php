<?php

namespace App\Filament\Resources\Cities\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('country.name')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('normalised_name')
                    ->searchable(),
                TextColumn::make('entity_id')
                    ->searchable(),
                IconColumn::make('is_main_city')
                    ->boolean(),
                TextColumn::make('subregion')
                    ->searchable(),
                TextColumn::make('mainCity.name')
                    ->searchable(),
                TextColumn::make('type')
                    ->searchable(),
                TextColumn::make('radius_distance')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('radius_unit')
                    ->searchable(),
                TextColumn::make('latitude')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('longitude')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('venues_count')
                    ->counts('venues')
                    ->label('Venues')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->paginated([25, 50, 100, 250])
            ->defaultPaginationPageOption(50)
            ->filters([
                TernaryFilter::make('is_main_city')
                    ->label('Main City')
                    ->placeholder('All cities')
                    ->trueLabel('Main cities only')
                    ->falseLabel('Non-main cities only'),
                SelectFilter::make('country_id')
                    ->relationship('country', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Country'),
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
