<?php

namespace App\Filament\Resources\Location;

use App\Filament\Resources\Location\Pages\CreateCity;
use App\Filament\Resources\Location\Pages\EditCity;
use App\Filament\Resources\Location\Pages\ListCities;
use App\Filament\Traits\HasRoleBasedAccess;
use App\Models\City;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CityResource extends Resource
{
    use HasRoleBasedAccess;

    protected static ?string $model = City::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static \UnitEnum|string|null $navigationGroup = 'Location';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('country_id')
                ->relationship('country', 'name')
                ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name} ({$record->code})")
                ->searchable()
                ->preload()
                ->required(),
            TextInput::make('name')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('country.name')->label('Country')->searchable()->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('local_areas_count')->counts('localAreas')->label('Local Areas'),
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
            'index' => ListCities::route('/'),
            'create' => CreateCity::route('/create'),
            'edit' => EditCity::route('/{record}/edit'),
        ];
    }
}
