<?php

namespace App\Filament\Resources\Location\Pages;

use App\Filament\Resources\Location\CountryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCountry extends EditRecord
{
    protected static string $resource = CountryResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
