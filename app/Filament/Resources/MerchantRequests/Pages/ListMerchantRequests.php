<?php

namespace App\Filament\Resources\MerchantRequests\Pages;

use App\Filament\Resources\MerchantRequests\MerchantRequestResource;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Resources\Pages\ListRecords;

class ListMerchantRequests extends ListRecords
{
    protected static string $resource = MerchantRequestResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'pending' => Tab::make('Pending')
                ->query(fn ($query) => $query->where('status', 'pending')),
            'approved' => Tab::make('Approved')
                ->query(fn ($query) => $query->where('status', 'approved')),
            'rejected' => Tab::make('Rejected')
                ->query(fn ($query) => $query->where('status', 'rejected')),
        ];
    }
}
