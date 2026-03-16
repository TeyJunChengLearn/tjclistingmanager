<?php

namespace App\Filament\Resources\Location\Pages;

use App\Filament\Resources\Location\BuildingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBuilding extends EditRecord
{
    protected static string $resource = BuildingResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
