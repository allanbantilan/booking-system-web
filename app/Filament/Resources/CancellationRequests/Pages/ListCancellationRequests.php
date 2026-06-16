<?php

namespace App\Filament\Resources\CancellationRequests\Pages;

use App\Filament\Resources\CancellationRequests\CancellationRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListCancellationRequests extends ListRecords
{
    protected static string $resource = CancellationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
