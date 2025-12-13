<?php

namespace App\Filament\Resources\Venues\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class VenueForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('external_id')
                    ->default(null),
                Select::make('city_id')
                    ->relationship('city', 'name')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('url')
                    ->url()
                    ->default(null),
                TextInput::make('source')
                    ->default(null),
                Textarea::make('description')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('type_id')
                    ->default(null),
                TextInput::make('type_name')
                    ->default(null),
                TextInput::make('normalised_name')
                    ->default(null),
                TextInput::make('desktop_uri')
                    ->default(null),
                TextInput::make('mobile_uri')
                    ->default(null),
                TextInput::make('app_uri')
                    ->default(null),
                Toggle::make('is_new_venue')
                    ->required(),
                Textarea::make('raw_data')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('address')
                    ->default(null),
                TextInput::make('latitude')
                    ->numeric()
                    ->default(null),
                TextInput::make('longitude')
                    ->numeric()
                    ->default(null),
                Select::make('location_id')
                    ->relationship('location', 'name')
                    ->default(null),
                TextInput::make('phone')
                    ->tel()
                    ->default(null),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->default(null),
                TextInput::make('website')
                    ->url()
                    ->default(null),
                Textarea::make('opening_hours')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('rating')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('rating_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
