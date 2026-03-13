<?php

namespace App\Filament\Resources\Reservations\Tables;

use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Types\StatusType;

class ReservationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
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
                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(function ($state) {
                        if ($state instanceof StatusType) {
                            return $state->label();
                        }

                        return StatusType::tryFrom($state)?->label() ?? $state;
                    })
                    ->badge()
                    ->color(fn ($state) => $state === 'confirmed' ? 'success' : 'warning')
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
                SelectFilter::make('status')
                    ->options(StatusType::options()),
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
