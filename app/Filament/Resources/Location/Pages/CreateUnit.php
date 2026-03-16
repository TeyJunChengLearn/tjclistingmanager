<?php

namespace App\Filament\Resources\Location\Pages;

use App\Filament\Resources\Location\UnitResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUnit extends CreateRecord
{
    protected static string $resource = UnitResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        return $data;
    }
}
