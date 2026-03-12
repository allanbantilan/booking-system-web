<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\TagsInput;
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
                TextInput::make('quantity_label')
                    ->maxLength(30)
                    ->default('slot(s)')
                    ->helperText('Unit label beside quantity (e.g., seat(s), night(s)).'),
                TextInput::make('availability_label')
                    ->maxLength(30)
                    ->default('Slots left')
                    ->helperText('Label shown before remaining capacity.'),
                TextInput::make('meta_line')
                    ->maxLength(255)
                    ->helperText('Short static subtitle shown under the booking title.')
                    ->columnSpanFull(),
                TagsInput::make('amenities')
                    ->helperText('Enter amenity keys that map to UI labels and icons.')
                    ->placeholder('wifi, breakfast, parking')
                    ->columnSpanFull(),
            ]);
    }
}
