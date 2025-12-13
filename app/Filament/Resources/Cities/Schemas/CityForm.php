<?php

namespace App\Filament\Resources\Cities\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('country_id')
                    ->relationship('country', 'name')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('normalised_name')
                    ->default(null),
                TextInput::make('entity_id')
                    ->default(null),
                Toggle::make('is_main_city'),
                TextInput::make('subregion')
                    ->default(null),
                Select::make('main_city_id')
                    ->relationship('mainCity', 'name')
                    ->default(null),
                TextInput::make('type')
                    ->default('city'),
                TextInput::make('radius_distance')
                    ->numeric()
                    ->default(null),
                TextInput::make('radius_unit')
                    ->default(null),
                TextInput::make('latitude')
                    ->numeric()
                    ->default(null),
                TextInput::make('longitude')
                    ->numeric()
                    ->default(null),
            ]);
    }
}
