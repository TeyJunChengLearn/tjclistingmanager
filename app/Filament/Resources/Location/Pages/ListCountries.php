<?php

namespace App\Filament\Resources\Location\Pages;

use App\Filament\Resources\Location\CountryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCountries extends ListRecords
{
    protected static string $resource = CountryResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
