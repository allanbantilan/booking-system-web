<?php

namespace App\Filament\Resources\BackendUsers;

use App\Filament\Resources\BackendUsers\Pages\CreateBackendUser;
use App\Filament\Resources\BackendUsers\Pages\EditBackendUser;
use App\Filament\Resources\BackendUsers\Pages\ListBackendUsers;
use App\Filament\Resources\BackendUsers\Schemas\BackendUserForm;
use App\Filament\Resources\BackendUsers\Tables\BackendUsersTable;
use App\Models\BackendUser;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BackendUserResource extends Resource
{
    protected static ?string $model = BackendUser::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|UnitEnum|null $navigationGroup = 'Management';
    protected static ?string $navigationLabel = 'Backend Users';

    public static function form(Schema $schema): Schema
    {
        return BackendUserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BackendUsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBackendUsers::route('/'),
            'create' => CreateBackendUser::route('/create'),
            'edit' => EditBackendUser::route('/{record}/edit'),
        ];
    }
}
