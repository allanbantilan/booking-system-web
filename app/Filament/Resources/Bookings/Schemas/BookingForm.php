<?php

namespace App\Filament\Resources\Bookings\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookingForm
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
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                SpatieMediaLibraryFileUpload::make('images')
                    ->collection('images')
                    ->multiple()
                    ->image()
                    ->appendFiles()
                    ->saveRelationshipsUsing(fn (SpatieMediaLibraryFileUpload $component) => $component->saveUploadedFiles()),
                TextInput::make('location')
                    ->required()
                    ->maxLength(255),
                DateTimePicker::make('event_date')
                    ->required(),
                TextInput::make('capacity')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                Section::make('Availability & Display')
                    ->schema([
                        TextInput::make('availability_label')
                            ->label('Availability Label')
                            ->maxLength(30)
                            ->default('Slots left')
                            ->helperText('Label shown before the remaining slots count.'),
                        TextInput::make('quantity_label')
                            ->label('Quantity Label')
                            ->maxLength(30)
                            ->default('slot(s)')
                            ->helperText('Unit label beside quantity (e.g., seat(s), night(s)).'),
                        TextInput::make('meta_line')
                            ->label('Meta Line')
                            ->maxLength(255)
                            ->helperText('Short static subtitle shown under the booking title.')
                            ->columnSpanFull(),
                        TagsInput::make('amenities')
                            ->label('Amenities')
                            ->helperText('Enter amenity keys that map to UI labels and icons.')
                            ->placeholder('wifi, breakfast, parking')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                TextInput::make('price')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                TextInput::make('discount_percentage')
                    ->label('Discount %')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->default(0)
                    ->suffix('%'),
            ]);
    }
}
