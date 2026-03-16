<?php

namespace App\Filament\Resources\Location\Pages;

use App\Filament\Resources\Location\UnitResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUnit extends EditRecord
{
    protected static string $resource = UnitResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
