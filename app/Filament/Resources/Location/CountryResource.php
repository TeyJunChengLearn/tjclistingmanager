<?php

namespace App\Filament\Resources\Location;

use App\Filament\Resources\Location\Pages\CreateCountry;
use App\Filament\Resources\Location\Pages\EditCountry;
use App\Filament\Resources\Location\Pages\ListCountries;
use App\Filament\Traits\HasRoleBasedAccess;
use App\Models\Country;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CountryResource extends Resource
{
    use HasRoleBasedAccess;

    protected static ?string $model = Country::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;
    protected static \UnitEnum|string|null $navigationGroup = 'Location';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
            TextInput::make('code')
                ->label('ISO Code')
                ->required()
                ->maxLength(2)
                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                ->dehydrateStateUsing(fn (?string $state) => strtoupper($state ?? '')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('code')->searchable()->sortable(),
                TextColumn::make('cities_count')->counts('cities')->label('Cities'),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListCountries::route('/'),
            'create' => CreateCountry::route('/create'),
            'edit'   => EditCountry::route('/{record}/edit'),
        ];
    }
}
