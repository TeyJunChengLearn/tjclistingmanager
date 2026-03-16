<?php

namespace App\Filament\Resources\MyListing\Pages;

use App\Filament\Resources\MyListing\ListingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditListing extends EditRecord
{
    protected static string $resource = ListingResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
