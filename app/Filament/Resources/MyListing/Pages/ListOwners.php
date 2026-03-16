<?php

namespace App\Filament\Resources\MyListing\Pages;

use App\Filament\Resources\MyListing\OwnerResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOwners extends ListRecords
{
    protected static string $resource = OwnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label('Import from Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->url(OwnerResource::getUrl('import')),
            CreateAction::make(),
        ];
    }
}
