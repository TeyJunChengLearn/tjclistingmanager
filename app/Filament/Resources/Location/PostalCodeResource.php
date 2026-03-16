<?php

namespace App\Filament\Resources\Location;

use App\Filament\Resources\Location\Pages\CreatePostalCode;
use App\Filament\Resources\Location\Pages\EditPostalCode;
use App\Filament\Resources\Location\Pages\ListPostalCodes;
use App\Filament\Traits\HasRoleBasedAccess;
use App\Models\PostalCode;
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

class PostalCodeResource extends Resource
{
    use HasRoleBasedAccess;

    protected static ?string $model = PostalCode::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;
    protected static \UnitEnum|string|null $navigationGroup = 'Location';
    protected static ?int $navigationSort = 3;
    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('city_id')
                ->relationship('city', 'name', fn ($query) => $query->with('country'))
                ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name}, {$record->country->name}")
                ->searchable()
                ->preload()
                ->required(),
            TextInput::make('code')->label('Postal Code')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('city.country.name')->label('Country')->searchable()->sortable(),
                TextColumn::make('city.name')->label('City')->searchable()->sortable(),
                TextColumn::make('code')->label('Postal Code')->searchable()->sortable(),
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
            'index'  => ListPostalCodes::route('/'),
            'create' => CreatePostalCode::route('/create'),
            'edit'   => EditPostalCode::route('/{record}/edit'),
        ];
    }
}
