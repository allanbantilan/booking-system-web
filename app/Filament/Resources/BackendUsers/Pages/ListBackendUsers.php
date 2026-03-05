<?php

namespace App\Filament\Resources\BackendUsers\Pages;

use App\Filament\Resources\BackendUsers\BackendUserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBackendUsers extends ListRecords
{
    protected static string $resource = BackendUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
