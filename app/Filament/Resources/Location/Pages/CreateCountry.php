<?php

namespace App\Filament\Resources\Location\Pages;

use App\Filament\Resources\Location\CountryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCountry extends CreateRecord
{
    protected static string $resource = CountryResource::class;
}
