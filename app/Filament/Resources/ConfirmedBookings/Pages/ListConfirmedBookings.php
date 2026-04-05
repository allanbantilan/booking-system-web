<?php

namespace App\Filament\Resources\ConfirmedBookings\Pages;

use App\Filament\Resources\ConfirmedBookings\ConfirmedBookingResource;
use Filament\Resources\Pages\ListRecords;

class ListConfirmedBookings extends ListRecords
{
    protected static string $resource = ConfirmedBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
