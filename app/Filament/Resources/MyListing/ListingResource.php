<?php

namespace App\Filament\Resources\MyListing;

use App\Filament\Resources\MyListing\Pages\CreateListing;
use App\Filament\Resources\MyListing\Pages\EditListing;
use App\Filament\Resources\MyListing\Pages\ListListings;
use App\Filament\Resources\MyListing\RelationManagers\ActivitiesRelationManager;
use App\Filament\Resources\MyListing\OwnerResource;
use App\Filament\Traits\HasRoleBasedAccess;
use App\Models\UnitListing;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListingResource extends Resource
{
    use HasRoleBasedAccess;

    protected static ?string $model = UnitListing::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;
    protected static \UnitEnum|string|null $navigationGroup = 'My Listing';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Listing';
    protected static ?string $modelLabel = 'Listing';
    protected static ?string $pluralModelLabel = 'Listings';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()->role === 'admin') {
            return $query->with('owner.phoneNumbers');
        }

        $userId  = auth()->id();
        $teamIds = auth()->user()->teams()->pluck('teams.id');

        return $query->with('owner.phoneNumbers')->where(function ($q) use ($userId, $teamIds) {
            $q->whereIn('team_id', $teamIds)
              ->orWhere(function ($q2) use ($userId) {
                  $q2->whereNull('team_id')
                     ->whereHas('owner', fn ($o) => $o->where('user_id', $userId));
              });
        });
    }

    public static function form(Schema $schema): Schema
    {
        $userId = auth()->id();
        $isAdmin = auth()->user()->role === 'admin';

        return $schema->components([
            Select::make('owner_id')
                ->relationship('owner', 'name', fn ($query) => $isAdmin ? $query : $query->where('user_id', $userId))
                ->searchable()
                ->preload()
                ->required(),
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
                ->relationship('team', 'name', fn ($query) =>  $query->where(function ($q) use ($userId) {
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

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn (UnitListing $record): string => OwnerResource::getUrl('edit', ['record' => $record->owner_id]))
            ->columns([
                TextColumn::make('owner.name')
                    ->label('Owner')
                    ->searchable()
                    ->sortable()
                    ->description(fn (UnitListing $record): string =>
                        $record->owner?->phoneNumbers->pluck('phone_number')->join(' · ') ?? ''
                    ),
                TextColumn::make('unit_id')
                    ->label('Unit')
                    ->state(fn (UnitListing $record): string => implode('-', array_filter([
                        $record->unit?->block,
                        $record->unit?->level,
                        $record->unit?->unit_number,
                    ])) ?: '—')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->whereHas('unit', fn ($q) => $q
                            ->where('unit_number', 'like', "%{$search}%")
                            ->orWhere('block', 'like', "%{$search}%")
                            ->orWhere('level', 'like', "%{$search}%")
                        )
                    ),
                TextColumn::make('unit.building.name')->label('Building')->searchable()->sortable(),
                TextColumn::make('team.name')->label('Team')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('rental_price')
                    ->label('Rental')
                    ->state(fn (UnitListing $record): string => $record->is_rent_available
                        ? ($record->rental_price !== null ? 'RM ' . number_format((float) $record->rental_price, 2) : '—')
                        : 'No'
                    )
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('sale_price')
                    ->label('Sale')
                    ->state(fn (UnitListing $record): string => $record->is_sale_available
                        ? ($record->sale_price !== null ? 'RM ' . number_format((float) $record->sale_price, 2) : '—')
                        : 'No'
                    )
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('call_after')->date()->label('Call Back After')->sortable(),
                TextColumn::make('latestActivity.followed_up_at')
                    ->label('Last Follow-up')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('latestActivity.outcome_code')
                    ->label('Outcome')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'connected', 'interested', 'agreed' => 'success',
                        'no_answer', 'busy', 'voicemail'    => 'warning',
                        'not_interested', 'price_too_high'  => 'danger',
                        default                             => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'connected'      => 'Connected',
                        'no_answer'      => 'No Answer',
                        'busy'           => 'Busy',
                        'voicemail'      => 'Left Voicemail',
                        'interested'     => 'Interested',
                        'not_interested' => 'Not Interested',
                        'price_too_high' => 'Price Too High',
                        'negotiating'    => 'Negotiating',
                        'agreed'         => 'Agreed',
                        default          => '—',
                    })
                    ->placeholder('—'),
                TextColumn::make('latestActivity.user.name')
                    ->label('Followed By')
                    ->placeholder('—'),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('call_after_status')
                    ->label('Call Back Status')
                    ->options([
                        'can_call'    => 'Can call from today',
                        'cannot_call' => 'Cannot call yet',
                        'no_date'     => 'No date set',
                    ])
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'can_call'    => $query->whereNotNull('call_after')->where('call_after', '<=', now()->toDateString()),
                        'cannot_call' => $query->where('call_after', '>', now()->toDateString()),
                        'no_date'     => $query->whereNull('call_after'),
                        default       => $query,
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getRelationManagers(): array
    {
        return [
            ActivitiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListListings::route('/'),
            'create' => CreateListing::route('/create'),
            'edit'   => EditListing::route('/{record}/edit'),
        ];
    }
}
