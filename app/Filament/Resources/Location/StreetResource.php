<?php

namespace App\Filament\Resources\Location;

use App\Filament\Resources\Location\Pages\CreateStreet;
use App\Filament\Resources\Location\Pages\EditStreet;
use App\Filament\Resources\Location\Pages\ListStreets;
use App\Filament\Traits\HasRoleBasedAccess;
use App\Models\Street;
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

class StreetResource extends Resource
{
    use HasRoleBasedAccess;

    protected static ?string $model = Street::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;
    protected static \UnitEnum|string|null $navigationGroup = 'Location';
    protected static ?int $navigationSort = 5;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('local_area_id')
                ->relationship('localArea', 'name', fn ($query) => $query->with('city'))
                ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name}, {$record->city->name}")
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
                TextColumn::make('localArea.city.name')->label('City')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('localArea.name')->label('Local Area')->searchable()->sortable(),
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
            'index'  => ListStreets::route('/'),
            'create' => CreateStreet::route('/create'),
            'edit'   => EditStreet::route('/{record}/edit'),
        ];
    }
}
