<?php

namespace App\Filament\Resources\BackendUsers\Pages;

use App\Filament\Resources\BackendUsers\BackendUserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBackendUser extends EditRecord
{
    protected static string $resource = BackendUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
