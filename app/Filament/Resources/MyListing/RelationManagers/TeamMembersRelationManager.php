<?php

namespace App\Filament\Resources\MyListing\RelationManagers;

use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TeamMembersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $title = 'Members';

    public function isReadOnly(): bool
    {
        return $this->ownerRecord->user_id !== auth()->id();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        $isOwner = $this->ownerRecord->user_id === auth()->id();

        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('pivot.role')->label('Role')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'manager' => 'warning',
                        default   => 'gray',
                    }),
            ])
            ->recordActions($isOwner ? [
                DetachAction::make()->label('Remove')->requiresConfirmation(),
            ] : [])
            ->toolbarActions($isOwner ? [
                \Filament\Actions\BulkActionGroup::make([
                    DetachBulkAction::make()->label('Remove selected'),
                ]),
            ] : []);
    }
}
