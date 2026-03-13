<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use App\Models\Reservation;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $today = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();

        $confirmedReservations = Reservation::query()->where('status', 'confirmed');

        $totalSales = (float) $confirmedReservations->sum('total_price');
        $todaySales = (float) Reservation::query()
            ->where('status', 'confirmed')
            ->whereDate('created_at', $today)
            ->sum('total_price');
        $monthSales = (float) Reservation::query()
            ->where('status', 'confirmed')
            ->where('created_at', '>=', $monthStart)
            ->sum('total_price');

        $totalReservations = Reservation::query()->count();
        $pendingReservations = Reservation::query()->where('status', 'pending')->count();

        $successfulPayments = Payment::query()->where('status', 'succeeded')->count();

        return [
            Stat::make('Total Sales', number_format($totalSales, 2))
                ->description('Confirmed reservations')
                ->color('success'),
            Stat::make('Sales Today', number_format($todaySales, 2))
                ->description($today->toFormattedDateString()),
            Stat::make('Sales This Month', number_format($monthSales, 2))
                ->description($monthStart->format('M Y')),
            Stat::make('Total Reservations', (string) $totalReservations),
            Stat::make('Pending Reservations', (string) $pendingReservations)
                ->color('warning'),
            Stat::make('Successful Payments', (string) $successfulPayments)
                ->color('success'),
        ];
    }
}
