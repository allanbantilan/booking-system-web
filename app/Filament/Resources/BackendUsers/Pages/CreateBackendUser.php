<?php

namespace App\Filament\Resources\BackendUsers\Pages;

use App\Filament\Resources\BackendUsers\BackendUserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBackendUser extends CreateRecord
{
    protected static string $resource = BackendUserResource::class;
}
