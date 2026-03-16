<?php

namespace App\Filament\Resources\Roles\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('guard_name')
                    ->default('web')
                    ->required(),
                CheckboxList::make('allowed_resources')
                    ->label('Allowed Pages')
                    ->options(function () {
                        $ungrouped = collect(\Filament\Facades\Filament::getPanel('admin')->getResources())
                            ->reject(fn ($resource) => $resource === \App\Filament\Resources\Roles\RoleResource::class)
                            ->reject(fn ($resource) => str_contains($resource, 'Resources\\Location\\'))
                            ->reject(fn ($resource) => str_contains($resource, 'Resources\\MyListing\\'))
                            ->mapWithKeys(fn ($resource) => [
                                $resource => str(class_basename($resource))
                                    ->beforeLast('Resource')
                                    ->headline()
                                    ->toString(),
                            ]);

                        return collect([
                            'location_group'   => 'Location',
                            'my_listing_group' => 'My Listing',
                        ])
                            ->merge($ungrouped)
                            ->toArray();
                    })
                    ->afterStateHydrated(function (CheckboxList $component, ?array $state): void {
                        if (empty($state)) return;

                        $migrated = collect($state);

                        // Migrate old individual Location class names → location_group
                        if ($migrated->contains(fn ($v) => str_contains((string) $v, 'Resources\\Location\\'))) {
                            $migrated = $migrated
                                ->reject(fn ($v) => str_contains((string) $v, 'Resources\\Location\\'))
                                ->push('location_group');
                        }

                        // Migrate old individual MyListing class names → my_listing_group
                        if ($migrated->contains(fn ($v) => str_contains((string) $v, 'Resources\\MyListing\\'))) {
                            $migrated = $migrated
                                ->reject(fn ($v) => str_contains((string) $v, 'Resources\\MyListing\\'))
                                ->push('my_listing_group');
                        }

                        $component->state($migrated->unique()->values()->toArray());
                    })
                    ->columns(2)
                    ->gridDirection('row'),
            ]);
    }
}
