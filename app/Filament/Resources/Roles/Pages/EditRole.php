<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;
    protected array $permissionIds = [];

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->permissionIds = [];

        foreach ($data as $key => $value) {
            if (Str::startsWith($key, 'permissions_')) {
                $this->permissionIds = array_merge($this->permissionIds, $value ?? []);
                unset($data[$key]);
            }
        }

        $this->permissionIds = array_values(array_unique($this->permissionIds));

        return $data;
    }

    protected function afterSave(): void
    {
        $permissions = Permission::query()
            ->whereIn('id', $this->permissionIds)
            ->pluck('name')
            ->all();

        $this->record->syncPermissions($permissions);
    }

    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl('index');
    }
}
