<?php

namespace App\Filament\Resources\Location;

use App\Filament\Resources\Location\Pages\CreateBuilding;
use App\Filament\Resources\Location\Pages\EditBuilding;
use App\Filament\Resources\Location\Pages\ListBuildings;
use App\Filament\Traits\HasRoleBasedAccess;
use App\Models\Building;
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

class BuildingResource extends Resource
{
    use HasRoleBasedAccess;

    protected static ?string $model = Building::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;
    protected static \UnitEnum|string|null $navigationGroup = 'Location';
    protected static ?int $navigationSort = 6;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('street_id')
                ->relationship('street', 'name', fn ($query) => $query->with('localArea'))
                ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name}, {$record->localArea->name}")
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
                TextColumn::make('street.localArea.name')->label('Local Area')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('street.name')->label('Street')->searchable()->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
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
            'index'  => ListBuildings::route('/'),
            'create' => CreateBuilding::route('/create'),
            'edit'   => EditBuilding::route('/{record}/edit'),
        ];
    }
}
