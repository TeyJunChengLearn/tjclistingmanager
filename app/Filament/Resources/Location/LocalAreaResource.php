<?php

namespace App\Filament\Resources\Location;

use App\Filament\Resources\Location\Pages\CreateLocalArea;
use App\Filament\Resources\Location\Pages\EditLocalArea;
use App\Filament\Resources\Location\Pages\ListLocalAreas;
use App\Filament\Traits\HasRoleBasedAccess;
use App\Models\LocalArea;
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

class LocalAreaResource extends Resource
{
    use HasRoleBasedAccess;

    protected static ?string $model = LocalArea::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;
    protected static \UnitEnum|string|null $navigationGroup = 'Location';
    protected static ?int $navigationSort = 4;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('city_id')
                ->relationship('city', 'name', fn ($query) => $query->with('country'))
                ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name}, {$record->country->name}")
                ->searchable()
                ->preload()
                ->live()
                ->required(),
            Select::make('postal_code_id')
                ->relationship('postalCode', 'code', fn ($query, $get) => $query->when($get('city_id'), fn ($q, $cityId) => $q->where('city_id', $cityId)))
                ->searchable()
                ->preload()
                ->nullable(),
            TextInput::make('name')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('city.country.name')->label('Country')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('city.name')->label('City')->searchable()->sortable(),
                TextColumn::make('postalCode.code')->label('Postal Code')->searchable(),
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
            'index'  => ListLocalAreas::route('/'),
            'create' => CreateLocalArea::route('/create'),
            'edit'   => EditLocalArea::route('/{record}/edit'),
        ];
    }
}
