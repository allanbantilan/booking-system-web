<?php

namespace App\Filament\Resources\MerchantRequests;

use App\Filament\Resources\MerchantRequests\Pages\ListMerchantRequests;
use App\Filament\Resources\MerchantRequests\Pages\ViewMerchantRequest;
use App\Filament\Resources\MerchantRequests\Tables\MerchantRequestsTable;
use App\Models\MerchantRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class MerchantRequestResource extends Resource
{
    protected static ?string $model = MerchantRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;
    protected static string|UnitEnum|null $navigationGroup = 'Management';
    protected static ?string $navigationLabel = 'Merchant Requests';

    public static function table(Table $table): Table
    {
        return MerchantRequestsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->latest();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMerchantRequests::route('/'),
            'view' => ViewMerchantRequest::route('/{record}'),
        ];
    }
}
