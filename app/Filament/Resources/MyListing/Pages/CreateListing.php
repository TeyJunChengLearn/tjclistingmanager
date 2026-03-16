<?php

namespace App\Filament\Resources\MyListing\Pages;

use App\Filament\Resources\MyListing\ListingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateListing extends CreateRecord
{
    protected static string $resource = ListingResource::class;
}
