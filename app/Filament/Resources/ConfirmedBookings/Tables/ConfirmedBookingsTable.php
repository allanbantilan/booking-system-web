<?php

namespace App\Filament\Resources\ConfirmedBookings\Tables;

use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ConfirmedBookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('booking.title')
                    ->label('Booking')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_price')
                    ->money('PHP')
                    ->label('Total')
                    ->sortable(),
                BadgeColumn::make('payment.status')
                    ->label('Payment')
                    ->formatStateUsing(fn ($state) => $state ?: 'n/a')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'succeeded',
                        'danger' => 'failed',
                    ])
                    ->sortable(),
                TextColumn::make('payment.provider')
                    ->label('Provider')
                    ->formatStateUsing(fn ($state) => $state ?: 'n/a')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'pending' => 'Pending',
                        'succeeded' => 'Succeeded',
                        'failed' => 'Failed',
                    ])
                    ->query(function ($query, array $data) {
                        if (!($data['value'] ?? null)) {
                            return;
                        }

                        $query->whereHas('payment', function ($paymentQuery) use ($data): void {
                            $paymentQuery->where('status', $data['value']);
                        });
                    }),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
