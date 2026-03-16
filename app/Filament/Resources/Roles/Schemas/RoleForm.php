<?php

namespace App\Filament\Resources\Roles\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        $permissions = Permission::query()
            ->where('guard_name', 'backend')
            ->orderBy('name')
            ->get();

        $groupedPermissions = $permissions
            ->groupBy(fn (Permission $permission) => self::groupLabel($permission->name))
            ->mapWithKeys(fn ($group, string $label) => [
                $label => $group
                    ->mapWithKeys(fn (Permission $permission) => [
                        $permission->id => self::optionLabel($permission->name),
                    ])
                    ->all(),
            ])
            ->all();

        $permissionFields = [];

        foreach ($groupedPermissions as $label => $options) {
            $optionIds = array_keys($options);
            $slug = Str::slug($label, '_');

            $permissionFields[] = CheckboxList::make("permissions_{$slug}")
                ->label($label)
                ->options($options)
                ->columns(2)
                ->bulkToggleable()
                ->default(function (?Role $record) use ($optionIds): array {
                    if (! $record) {
                        return [];
                    }

                    $current = $record->permissions()->pluck('id')->all();

                    return array_values(array_intersect($current, $optionIds));
                });
        }

        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->where('guard_name', 'backend'))
                    ->disabled(fn (?Role $record): bool => $record !== null)
                    ->hidden(fn (?Role $record): bool => $record !== null),
                Hidden::make('guard_name')
                    ->default('backend'),
                ...$permissionFields,
            ]);
    }

    private static function groupLabel(string $permission): string
    {
        if (str_contains($permission, '.')) {
            $entity = Str::before($permission, '.');
        } else {
            $entity = $permission;
            foreach (['view any ', 'view ', 'create ', 'update ', 'delete ', 'upload '] as $prefix) {
                if (str_starts_with($entity, $prefix)) {
                    $entity = substr($entity, strlen($prefix));
                    break;
                }
            }
        }

        $entity = Str::of($entity)
            ->replace('_', ' ')
            ->replace('.', ' ')
            ->trim()
            ->toString();

        return Str::title($entity ?: 'Other');
    }

    private static function optionLabel(string $permission): string
    {
        if (str_contains($permission, '.')) {
            $permission = Str::of($permission)
                ->replace('_', ' ')
                ->replace('.', ' ')
                ->trim()
                ->toString();
        }

        return Str::title($permission);
    }
}
