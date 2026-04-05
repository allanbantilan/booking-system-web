<?php

namespace App\Filament\Resources\ConfirmedBookings;

use App\Filament\Resources\ConfirmedBookings\Pages\ListConfirmedBookings;
use App\Filament\Resources\ConfirmedBookings\Tables\ConfirmedBookingsTable;
use App\Models\Reservation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ConfirmedBookingResource extends Resource
{
    protected static ?string $model = Reservation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;
    protected static string|UnitEnum|null $navigationGroup = 'Sales';
    protected static ?string $navigationLabel = 'Confirmed Bookings';

    public static function table(Table $table): Table
    {
        return ConfirmedBookingsTable::configure($table);
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth('backend')->user();

        return (bool) $user?->hasAnyRole(['super_admin', 'merchant']);
    }

    public static function canViewAny(): bool
    {
        $user = auth('backend')->user();

        return (bool) $user?->hasAnyRole(['super_admin', 'merchant']);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['booking', 'user', 'payment'])
            ->where('status', 'confirmed');

        $user = auth('backend')->user();

        if ($user && $user->hasRole('merchant')) {
            $query->whereHas('booking', fn (Builder $bookingQuery) => $bookingQuery->where('created_by', $user->id));
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConfirmedBookings::route('/'),
        ];
    }
}
