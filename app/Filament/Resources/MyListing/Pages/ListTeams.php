<?php

namespace App\Filament\Resources\MyListing\Pages;

use App\Filament\Resources\MyListing\TeamResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTeams extends ListRecords
{
    protected static string $resource = TeamResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
