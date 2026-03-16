<?php

namespace App\Filament\Resources\MyListing\RelationManagers;

use App\Models\UnitListing;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ListingsRelationManager extends RelationManager
{
    protected static string $relationship = 'listings';

    protected static ?string $title = 'Listings';

    public function form(Schema $schema): Schema
    {
        $userId = auth()->id();
        $isAdmin = auth()->user()->role === 'admin';

        return $schema->components([
            Select::make('unit_id')
                ->relationship('unit', 'unit_number', fn ($query) => $isAdmin
                    ? $query->with('building')
                    : $query->where('user_id', $userId)->with('building'))
                ->getOptionLabelFromRecordUsing(function ($record) {
                    $unitName = implode('-', array_filter([
                        $record->block,
                        $record->level,
                        $record->unit_number,
                    ]));
                    return "{$unitName} — {$record->building->name}";
                })
                ->searchable()
                ->preload()
                ->required(),
            Select::make('team_id')
                ->relationship('team', 'name', fn ($query) => $query->where(function ($q) use ($userId) {
                    $q->where('user_id', $userId)
                      ->orWhereHas('users', fn ($sub) => $sub->where('users.id', $userId));
                }))
                ->searchable()
                ->preload()
                ->required(),
            TextInput::make('rental_price')
                ->numeric()
                ->prefix('RM')
                ->nullable(),
            TextInput::make('sale_price')
                ->numeric()
                ->prefix('RM')
                ->nullable(),
            Toggle::make('is_rent_available')->label('Available for Rent'),
            Toggle::make('is_sale_available')->label('Available for Sale'),
            DatePicker::make('call_after')->label('Call Back After')->nullable(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('unit_id')
                    ->label('Unit')
                    ->state(fn (UnitListing $record): string => implode('-', array_filter([
                        $record->unit?->block,
                        $record->unit?->level,
                        $record->unit?->unit_number,
                    ])) ?: '—'),
                TextColumn::make('unit.building.name')->label('Building'),
                TextColumn::make('team.name')->label('Team'),
                TextColumn::make('rental_price')
                    ->label('Rental')
                    ->state(fn (UnitListing $record): string => $record->is_rent_available
                        ? ($record->rental_price !== null ? 'RM ' . number_format((float) $record->rental_price, 2) : '—')
                        : 'No'
                    ),
                TextColumn::make('sale_price')
                    ->label('Sale')
                    ->state(fn (UnitListing $record): string => $record->is_sale_available
                        ? ($record->sale_price !== null ? 'RM ' . number_format((float) $record->sale_price, 2) : '—')
                        : 'No'
                    ),
                TextColumn::make('call_after')->date()->label('Call Back After'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                CreateAction::make(),
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
