<?php

namespace App\Filament\Resources\Location\Pages;

use App\Filament\Resources\Location\LocalAreaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLocalArea extends EditRecord
{
    protected static string $resource = LocalAreaResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
