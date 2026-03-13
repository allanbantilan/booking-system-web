<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\FrequentBookingsTable;
use App\Filament\Widgets\SalesStatsOverview;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Pages\Page;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class StatisticsOverview extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;
    protected static string|UnitEnum|null $navigationGroup = 'Statistics';
    protected static ?string $navigationLabel = 'Overview';
    protected static ?string $title = 'Statistics Overview';

    protected string $view = 'filament.pages.statistics-overview';

    protected function getHeaderWidgets(): array
    {
        return [
            SalesStatsOverview::class,
            FrequentBookingsTable::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        $range = request()->string('range')->value() ?? '30d';

        if (!in_array($range, ['7d', '30d', '6m', '1y', 'all'], true)) {
            $range = '30d';
        }

        return [
            ActionGroup::make([
                Action::make('range7d')
                    ->label('Last 7 days')
                    ->url(static::getUrl(['range' => '7d'])),
                Action::make('range30d')
                    ->label('Last 30 days')
                    ->url(static::getUrl(['range' => '30d'])),
                Action::make('range6m')
                    ->label('Last 6 months')
                    ->url(static::getUrl(['range' => '6m'])),
                Action::make('range1y')
                    ->label('Last 1 year')
                    ->url(static::getUrl(['range' => '1y'])),
                Action::make('rangeAll')
                    ->label('All time')
                    ->url(static::getUrl(['range' => 'all'])),
            ])
                ->label('Date range: ' . $this->getRangeLabel($range))
                ->button()
                ->size('sm')
                ->color('gray'),
        ];
    }

    private function getRangeLabel(string $range): string
    {
        return match ($range) {
            '7d' => 'Last 7 days',
            '30d' => 'Last 30 days',
            '6m' => 'Last 6 months',
            '1y' => 'Last 1 year',
            default => 'All time',
        };
    }
}
