<?php

namespace App\Filament\Resources\Venues\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class VenuesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('external_id')
                    ->searchable(),
                TextColumn::make('city.name')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('url')
                    ->searchable(),
                TextColumn::make('source')
                    ->searchable(),
                TextColumn::make('type_id')
                    ->searchable(),
                TextColumn::make('type_name')
                    ->searchable(),
                TextColumn::make('normalised_name')
                    ->searchable(),
                TextColumn::make('desktop_uri')
                    ->searchable(),
                TextColumn::make('mobile_uri')
                    ->searchable(),
                TextColumn::make('app_uri')
                    ->searchable(),
                IconColumn::make('is_new_venue')
                    ->boolean(),
                TextColumn::make('address')
                    ->searchable(),
                TextColumn::make('latitude')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('longitude')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('location.name')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('website')
                    ->searchable(),
                TextColumn::make('rating')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('rating_count')
                    ->numeric()
                    ->sortable(),
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
                    ->label('Active Venues')
                    ->placeholder('All venues')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
                SelectFilter::make('city_id')
                    ->relationship('city', 'name')
                    ->searchable()
                    ->preload()
                    ->label('City'),
                SelectFilter::make('source')
                    ->options(function () {
                        return \App\Models\Venue::distinct()->pluck('source', 'source')->filter()->toArray();
                    })
                    ->searchable()
                    ->label('Source'),
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
