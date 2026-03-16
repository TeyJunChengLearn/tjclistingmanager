<?php

namespace App\Filament\Resources\Location\Pages;

use App\Filament\Resources\Location\CityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCities extends ListRecords
{
    protected static string $resource = CityResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
