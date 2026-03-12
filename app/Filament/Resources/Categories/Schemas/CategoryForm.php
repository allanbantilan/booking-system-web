<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state)))
                    ->helperText('Displayed to customers and used for grouping bookings.'),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->helperText('URL-friendly identifier auto-filled from the name.'),
                TextInput::make('color')
                    ->maxLength(20)
                    ->default('slate')
                    ->helperText('Accent color key used by the UI (e.g., cyan, amber, pink).'),
                TextInput::make('badge_label')
                    ->maxLength(50)
                    ->helperText('Short label shown on the booking card badge.'),
            ]);
    }
}
