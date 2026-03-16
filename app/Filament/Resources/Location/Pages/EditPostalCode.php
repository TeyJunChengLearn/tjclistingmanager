<?php

namespace App\Filament\Resources\Location\Pages;

use App\Filament\Resources\Location\PostalCodeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPostalCode extends EditRecord
{
    protected static string $resource = PostalCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
