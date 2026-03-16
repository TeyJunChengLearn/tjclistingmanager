<?php

namespace App\Filament\Resources\Location\Pages;

use App\Filament\Resources\Location\PostalCodeResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePostalCode extends CreateRecord
{
    protected static string $resource = PostalCodeResource::class;
}
