<?php

namespace App\Filament\Resources\Bookings\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use App\Models\Booking;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Select::make('booking_type')
                    ->label('Booking Type')
                    ->options(Booking::typeOptions())
                    ->required()
                    ->default(Booking::TYPE_EVENT)
                    ->reactive()
                    ->afterStateUpdated(function ($state, Get $get, Set $set, $old): void {
                        $defaults = Booking::typeDefaults((string) $state);
                        $previousDefaults = Booking::typeDefaults((string) $old);

                        $currentQuantity = (string) ($get('quantity_label') ?? '');
                        $currentAvailability = (string) ($get('availability_label') ?? '');

                        $allDefaults = array_map(
                            fn (array $item) => [$item['quantity_label'], $item['availability_label']],
                            array_map(
                                fn (string $type) => Booking::typeDefaults($type),
                                array_keys(Booking::typeOptions())
                            )
                        );
                        $flatDefaults = array_unique(array_merge(...$allDefaults));

                        $shouldReplaceQuantity = $currentQuantity === '' || $currentQuantity === ($previousDefaults['quantity_label'] ?? '')
                            || in_array($currentQuantity, $flatDefaults, true);
                        $shouldReplaceAvailability = $currentAvailability === '' || $currentAvailability === ($previousDefaults['availability_label'] ?? '')
                            || in_array($currentAvailability, $flatDefaults, true);

                        if ($shouldReplaceQuantity) {
                            $set('quantity_label', $defaults['quantity_label']);
                        }

                        if ($shouldReplaceAvailability) {
                            $set('availability_label', $defaults['availability_label']);
                        }
                    }),
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
                    ->label(fn (Get $get) => $get('booking_type') === Booking::TYPE_EVENT ? 'Event Date' : 'Booking Date')
                    ->helperText(fn (Get $get) => $get('booking_type') === Booking::TYPE_EVENT
                        ? 'Required for event listings.'
                        : 'Optional for listings without a fixed date.'
                    )
                    ->required(fn (Get $get) => $get('booking_type') === Booking::TYPE_EVENT)
                    ->nullable(),
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
                TextInput::make('extra_rate')
                    ->label('Extra Rate')
                    ->numeric()
                    ->minValue(0)
                    ->helperText('Optional. For accommodation/rental: added per extra night/day per room/unit. If empty, price is multiplied by nights/days.')
                    ->visible(fn (Get $get) => in_array($get('booking_type'), [Booking::TYPE_ACCOMMODATION, Booking::TYPE_RENTAL], true)),
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
