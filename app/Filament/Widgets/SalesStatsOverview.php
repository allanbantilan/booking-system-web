<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use App\Filament\Widgets\Concerns\HasDateRange;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesStatsOverview extends StatsOverviewWidget
{
    use HasDateRange;

    protected static bool $isDiscovered = false;

    protected int | array | null $columns = ['@xl' => 5, '!@lg' => 2];

    protected function getStats(): array
    {
        $rangeStart = $this->getRangeStart();
        $rangeLabel = $this->getRangeLabel();
        $merchantId = auth('backend')->user()?->id;
        $isMerchant = auth('backend')->user()?->hasRole('merchant') ?? false;
        $isSuperAdmin = auth('backend')->user()?->hasRole('super_admin') ?? false;

        $confirmedReservations = Reservation::query()
            ->where('status', 'confirmed')
            ->when($isMerchant, fn ($query) => $query->whereHas('booking', fn ($bookingQuery) => $bookingQuery->where('created_by', $merchantId)))
            ->when($rangeStart, fn ($query) => $query->where('created_at', '>=', $rangeStart));

        $totalSales = (float) $confirmedReservations->sum('total_price');
        $totalReservations = (int) $confirmedReservations->count();

        $pendingReservations = Reservation::query()
            ->where('status', 'pending')
            ->when($isMerchant, fn ($query) => $query->whereHas('booking', fn ($bookingQuery) => $bookingQuery->where('created_by', $merchantId)))
            ->when($rangeStart, fn ($query) => $query->where('created_at', '>=', $rangeStart))
            ->count();

        $stats = [
            Stat::make("Sales ({$rangeLabel})", number_format($totalSales, 2))
                ->description('Confirmed reservations revenue')
                ->color('success'),
            Stat::make("Reservations ({$rangeLabel})", (string) $totalReservations),
            Stat::make("Pending ({$rangeLabel})", (string) $pendingReservations)
                ->color('warning'),
        ];

        if ($isSuperAdmin) {
            $successfulPayments = Payment::query()
                ->where('status', 'succeeded')
                ->when($rangeStart, fn ($query) => $query->where('created_at', '>=', $rangeStart))
                ->count();

            $repeatUsers = Reservation::query()
                ->select('user_id', DB::raw('count(*) as total'))
                ->where('status', 'confirmed')
                ->when($rangeStart, fn ($query) => $query->where('created_at', '>=', $rangeStart))
                ->groupBy('user_id')
                ->get();

            $totalUsers = $repeatUsers->count();
            $retainedUsers = $repeatUsers->filter(fn ($row) => (int) $row->total >= 2)->count();

            $retentionRate = $totalUsers > 0
                ? round(($retainedUsers / $totalUsers) * 100, 1)
                : 0;

            $stats[] = Stat::make("Successful Payments ({$rangeLabel})", (string) $successfulPayments)
                ->color('success');
            $stats[] = Stat::make("Retention ({$rangeLabel})", $retentionRate . '%')
                ->description("{$retainedUsers}/{$totalUsers} repeat users")
                ->color($retentionRate >= 50 ? 'success' : 'warning');
        }

        return $stats;
    }
}
