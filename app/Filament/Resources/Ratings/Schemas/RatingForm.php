<?php

namespace App\Filament\Resources\Ratings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RatingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('venue_id')
                    ->relationship('venue', 'name')
                    ->required(),
                TextInput::make('reviewer_name')
                    ->default(null),
                TextInput::make('reviewer_email')
                    ->email()
                    ->default(null),
                TextInput::make('rating')
                    ->numeric()
                    ->default(null),
                Textarea::make('comment')
                    ->default(null)
                    ->columnSpanFull(),
                Toggle::make('is_verified')
                    ->required(),
                TextInput::make('weighted_average')
                    ->numeric()
                    ->default(null),
                TextInput::make('count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('cleanliness_avg')
                    ->numeric()
                    ->default(null),
                TextInput::make('cleanliness_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('staff_avg')
                    ->numeric()
                    ->default(null),
                TextInput::make('staff_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('atmosphere_avg')
                    ->numeric()
                    ->default(null),
                TextInput::make('atmosphere_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('display_average')
                    ->numeric()
                    ->default(null),
            ]);
    }
}
