<?php

namespace App\Filament\Widgets\Concerns;

use Carbon\Carbon;

trait HasDateRange
{
    protected function getRange(): string
    {
        $range = request()->string('range')->value() ?? '30d';

        return in_array($range, ['7d', '30d', '6m', '1y', 'all'], true) ? $range : '30d';
    }

    protected function getRangeStart(): ?Carbon
    {
        $range = $this->getRange();

        return match ($range) {
            '7d' => Carbon::now()->subDays(7),
            '30d' => Carbon::now()->subDays(30),
            '6m' => Carbon::now()->subMonths(6),
            '1y' => Carbon::now()->subYear(),
            default => null,
        };
    }

    protected function getRangeLabel(): string
    {
        return match ($this->getRange()) {
            '7d' => 'Last 7 days',
            '30d' => 'Last 30 days',
            '6m' => 'Last 6 months',
            '1y' => 'Last 1 year',
            default => 'All time',
        };
    }
}
