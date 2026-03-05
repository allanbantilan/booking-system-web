<?php

namespace App\Filament\Resources\Events\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                TextInput::make('location')
                    ->required()
                    ->maxLength(255),
                DateTimePicker::make('event_date')
                    ->required(),
                TextInput::make('capacity')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                TextInput::make('price')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                Select::make('created_by')
                    ->relationship('creator', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
            ]);
    }
}
