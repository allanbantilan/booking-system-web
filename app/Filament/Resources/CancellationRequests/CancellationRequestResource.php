<?php

namespace App\Filament\Resources\CancellationRequests;

use App\Filament\Resources\CancellationRequests\Pages\ListCancellationRequests;
use App\Filament\Resources\CancellationRequests\Tables\CancellationRequestsTable;
use App\Models\ReservationCancellationRequest;
use App\Types\CancellationRequestStatus;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CancellationRequestResource extends Resource
{
    protected static ?string $model = ReservationCancellationRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedXCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Sales';

    protected static ?string $navigationLabel = 'Cancellation Requests';

    public static function table(Table $table): Table
    {
        return CancellationRequestsTable::configure($table);
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

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()
            ->where('status', CancellationRequestStatus::Requested)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['booking', 'user', 'reservation.payment.receipt']);

        $user = auth('backend')->user();

        if ($user && $user->hasRole('merchant') && ! $user->hasRole('super_admin')) {
            $query->where(function (Builder $requestQuery) use ($user): void {
                $requestQuery
                    ->where('merchant_id', $user->id)
                    ->orWhereHas('booking', fn (Builder $bookingQuery) => $bookingQuery->where('created_by', $user->id));
            });
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCancellationRequests::route('/'),
        ];
    }
}
