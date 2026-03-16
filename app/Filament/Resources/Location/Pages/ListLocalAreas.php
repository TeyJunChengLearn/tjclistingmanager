<?php

namespace App\Filament\Resources\Location\Pages;

use App\Filament\Resources\Location\LocalAreaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLocalAreas extends ListRecords
{
    protected static string $resource = LocalAreaResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
