<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDateRange;
use App\Models\Booking;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class FrequentBookingsTable extends TableWidget
{
    use HasDateRange;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Frequent Bookings')
            ->query($this->getQuery())
            ->columns([
                TextColumn::make('title')
                    ->label('Booking')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('reservations_count')
                    ->label('Reservations')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_quantity')
                    ->label('Total Qty')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_revenue')
                    ->label('Revenue')
                    ->money('PHP')
                    ->sortable(),
                TextColumn::make('last_reserved_at')
                    ->label('Last Booking')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('reservations_count', 'desc')
            ->paginated(false);
    }

    protected function getQuery(): Builder
    {
        $rangeStart = $this->getRangeStart();
        $merchantId = auth('backend')->user()?->id;
        $isMerchant = auth('backend')->user()?->hasRole('merchant') ?? false;

        return Booking::query()
            ->select([
                'bookings.id',
                'bookings.title',
                DB::raw('count(reservations.id) as reservations_count'),
                DB::raw('sum(reservations.quantity) as total_quantity'),
                DB::raw('sum(reservations.total_price) as total_revenue'),
                DB::raw('max(reservations.created_at) as last_reserved_at'),
            ])
            ->join('reservations', 'reservations.booking_id', '=', 'bookings.id')
            ->where('reservations.status', 'confirmed')
            ->when($isMerchant, fn ($query) => $query->where('bookings.created_by', $merchantId))
            ->when($rangeStart, fn ($query) => $query->where('reservations.created_at', '>=', $rangeStart))
            ->groupBy('bookings.id', 'bookings.title')
            ->orderByDesc('reservations_count')
            ->limit(5);
    }
}
