<?php

namespace App\Filament\Resources\MyListing;

use App\Filament\Resources\MyListing\Pages\CreateTeam;
use App\Filament\Resources\MyListing\Pages\EditTeam;
use App\Filament\Resources\MyListing\Pages\ListTeams;
use App\Filament\Resources\MyListing\RelationManagers\TeamMembersRelationManager;
use App\Filament\Traits\HasRoleBasedAccess;
use App\Models\Team;
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
use Illuminate\Database\Eloquent\Builder;

class TeamResource extends Resource
{
    use HasRoleBasedAccess;

    protected static ?string $model = Team::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;
    protected static \UnitEnum|string|null $navigationGroup = 'My Listing';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        $userId = auth()->id();

        return parent::getEloquentQuery()->where(function (Builder $query) use ($userId) {
            $query->where('user_id', $userId)
                ->orWhereHas('users', fn (Builder $q) => $q->where('users.id', $userId));
        });
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        $userId = auth()->id();

        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('users_count')->counts('users')->label('Members'),
                TextColumn::make('role')
                    ->label('Your Role')
                    ->badge()
                    ->state(function (Team $record) use ($userId): string {
                        if ($record->user_id === $userId) {
                            return 'owner';
                        }
                        return $record->users()
                            ->where('users.id', $userId)
                            ->first()?->pivot->role ?? '—';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'owner'   => 'success',
                        'manager' => 'warning',
                        'member'  => 'gray',
                        default   => 'gray',
                    }),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (Team $record): bool => $record->user_id === $userId),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            TeamMembersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListTeams::route('/'),
            'create' => CreateTeam::route('/create'),
            'edit'   => EditTeam::route('/{record}/edit'),
        ];
    }
}
