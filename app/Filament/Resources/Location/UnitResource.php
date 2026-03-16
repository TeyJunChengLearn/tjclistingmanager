<?php

namespace App\Filament\Resources\Location;

use App\Filament\Resources\Location\Pages\CreateUnit;
use App\Filament\Resources\Location\Pages\EditUnit;
use App\Filament\Resources\Location\Pages\ListUnits;
use App\Filament\Traits\HasRoleBasedAccess;
use App\Models\Unit;
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
use Illuminate\Database\Eloquent\Builder;

class UnitResource extends Resource
{
    use HasRoleBasedAccess;

    protected static ?string $model = Unit::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;
    protected static \UnitEnum|string|null $navigationGroup = 'Location';
    protected static ?int $navigationSort = 7;
    protected static ?string $recordTitleAttribute = 'unit_number';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()->role === 'admin') {
            return $query;
        }

        return $query->where('user_id', auth()->id());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('building_id')
                ->relationship('building', 'name', fn ($query) => $query->with('street'))
                ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name}, {$record->street->name}")
                ->searchable()
                ->preload()
                ->required(),
            TextInput::make('block')->nullable(),
            TextInput::make('level')->nullable(),
            TextInput::make('unit_number')->label('Unit No.')->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('building.street.localArea.city.name')->label('City')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('building.name')->label('Building')->searchable()->sortable(),
                TextColumn::make('block')->searchable()->sortable(),
                TextColumn::make('level')->searchable()->sortable(),
                TextColumn::make('unit_number')->label('Unit No.')->searchable()->sortable(),
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
            'index'  => ListUnits::route('/'),
            'create' => CreateUnit::route('/create'),
            'edit'   => EditUnit::route('/{record}/edit'),
        ];
    }
}
