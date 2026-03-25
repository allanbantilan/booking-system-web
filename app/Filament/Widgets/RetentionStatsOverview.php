<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDateRange;
use App\Models\Reservation;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class RetentionStatsOverview extends StatsOverviewWidget
{
    use HasDateRange;

    protected static bool $isDiscovered = false;

    public static function canView(): bool
    {
        return auth('backend')->user()?->hasRole('super_admin') ?? false;
    }

    protected function getStats(): array
    {
        $rangeStart = $this->getRangeStart();
        $rangeLabel = $this->getRangeLabel();
        $merchantId = auth('backend')->user()?->id;
        $isMerchant = auth('backend')->user()?->hasRole('merchant') ?? false;

        $base = Reservation::query()
            ->select('user_id', DB::raw('count(*) as total'))
            ->where('status', 'confirmed')
            ->when($isMerchant, fn ($query) => $query->whereHas('booking', fn ($bookingQuery) => $bookingQuery->where('created_by', $merchantId)))
            ->when($rangeStart, fn ($query) => $query->where('created_at', '>=', $rangeStart))
            ->groupBy('user_id')
            ->get();

        $totalUsers = $base->count();
        $repeatUsers = $base->filter(fn ($row) => (int) $row->total >= 2)->count();

        $rate = $totalUsers > 0
            ? round(($repeatUsers / $totalUsers) * 100, 1)
            : 0;

        return [
            Stat::make("User Retention ({$rangeLabel})", $rate . '%')
                ->description("{$repeatUsers}/{$totalUsers} repeat customers")
                ->color($rate >= 50 ? 'success' : 'warning'),
        ];
    }
}
