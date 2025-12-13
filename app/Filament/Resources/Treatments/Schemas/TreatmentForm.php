<?php

namespace App\Filament\Resources\Treatments\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TreatmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('venue_id')
                    ->relationship('venue', 'name')
                    ->required(),
                TextInput::make('external_id')
                    ->default(null),
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Textarea::make('description')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('duration')
                    ->required()
                    ->numeric(),
                TextInput::make('min_duration')
                    ->numeric()
                    ->default(null),
                TextInput::make('max_duration')
                    ->numeric()
                    ->default(null),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('min_price')
                    ->numeric()
                    ->default(null)
                    ->prefix('$'),
                TextInput::make('max_price')
                    ->numeric()
                    ->default(null)
                    ->prefix('$'),
                TextInput::make('category')
                    ->default(null),
                TextInput::make('category_id')
                    ->default(null),
                TextInput::make('category_name')
                    ->default(null),
                Textarea::make('options')
                    ->default(null)
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
