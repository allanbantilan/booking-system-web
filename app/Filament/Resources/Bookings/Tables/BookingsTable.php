<?php

namespace App\Filament\Resources\Bookings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('images')
                    ->collection('images')
                    ->label('Images')
                    ->limit(1)
                    ->circular(),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('booking_type')
                    ->label('Type')
                    ->sortable(),
                TextColumn::make('location')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('event_date')
                    ->dateTime()
                    ->label('Booking Date')
                    ->sortable(),
                TextColumn::make('capacity')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('availability_label')
                    ->label('Availability Label')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('quantity_label')
                    ->label('Quantity Label')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('meta_line')
                    ->label('Meta Line')
                    ->limit(40)
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('amenities')
                    ->label('Amenities')
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : '')
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('price')
                    ->money('PHP')
                    ->sortable(),
                TextColumn::make('discount_percentage')
                    ->label('Discount %')
                    ->numeric()
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
