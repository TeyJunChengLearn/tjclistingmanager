<?php

namespace App\Filament\Resources\Location\Pages;

use App\Filament\Resources\Location\StreetResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStreet extends EditRecord
{
    protected static string $resource = StreetResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
