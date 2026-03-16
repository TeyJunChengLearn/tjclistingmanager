<?php

namespace App\Filament\Resources\MyListing;

use App\Filament\Resources\MyListing\Pages\CreateOwner;
use App\Filament\Resources\MyListing\Pages\EditOwner;
use App\Filament\Resources\MyListing\Pages\ImportOwnerListings;
use App\Filament\Resources\MyListing\Pages\ListOwners;
use App\Filament\Traits\HasRoleBasedAccess;
use App\Models\Activity;
use App\Models\File;
use App\Models\Fileable;
use App\Models\Owner;
use App\Models\Team;
use App\Models\Unit;
use App\Models\UnitListing;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class OwnerResource extends Resource
{
    use HasRoleBasedAccess;

    protected static ?string $model = Owner::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;
    protected static \UnitEnum|string|null $navigationGroup = 'My Listing';
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()->role === 'admin') {
            return $query;
        }

        $userId = auth()->id();

        return $query->where(function (Builder $query) use ($userId) {
            $query->where('user_id', $userId)
                ->orWhereHas('listings', fn (Builder $q) => $q
                    ->whereHas('team', fn (Builder $q) => $q
                        ->where('user_id', $userId)
                        ->orWhereHas('users', fn (Builder $q) => $q->where('users.id', $userId))
                    )
                );
        });
    }

    public static function buildUnitLabel(Unit $unit): string
    {
        $unitName = implode('-', array_filter([
            $unit->block,
            $unit->level,
            $unit->unit_number,
        ]));

        $address = implode(', ', array_filter([
            $unit->building?->name,
            $unit->building?->street?->name,
            $unit->building?->street?->localArea?->name,
            $unit->building?->street?->localArea?->city?->name,
        ]));

        return $address ? "{$unitName}, {$address}" : $unitName;
    }

    public static function form(Schema $schema): Schema
    {
        $userId  = auth()->id();
        $isAdmin = auth()->user()->role === 'admin';

        return $schema->components([
            TextInput::make('name')->required(),
            TextInput::make('ic')->label('IC / Passport')->nullable(),
            TextInput::make('email')->email()->nullable(),
            TextInput::make('mailing_address')->nullable(),
            Select::make('owner_type')
                ->options([
                    'individual' => 'Individual',
                    'company'    => 'Company',
                    'government' => 'Government',
                ])
                ->default('individual')
                ->required(),
            Repeater::make('phone_numbers_data')
                ->label('Phone Numbers')
                ->columnSpanFull()
                ->schema([
                    TextInput::make('phone_number')
                        ->label('Phone Number')
                        ->tel()
                        ->required(),
                    Select::make('status')
                        ->label('Status')
                        ->options([
                            'need_verify'  => 'Need Verify',
                            'active'       => 'Active',
                            'primary'      => 'Primary',
                            'inactive'     => 'Inactive',
                            'disconnected' => 'Disconnected',
                            'wrong_number' => 'Wrong Number',
                        ])
                        ->default('need_verify')
                        ->required(),
                ])
                ->defaultItems(0)
                ->addActionLabel('Add Phone Number')
                ->afterStateHydrated(function (Repeater $component, $record) {
                    if (! $record) {
                        return;
                    }
                    $component->state(
                        $record->phoneNumbers->map(fn ($phone) => [
                            'phone_number' => $phone->phone_number,
                            'type'         => $phone->type,
                            'status'       => $phone->pivot->status,
                        ])->toArray()
                    );
                })
                ->dehydrated(false),
            Repeater::make('listings_data')
                ->label('Listings')
                ->columnSpanFull()
                ->schema([
                    Hidden::make('listing_id'),
                    Hidden::make('activities_page')->default(1),
                    Hidden::make('activities_total')->default(0),
                    Select::make('unit_id')
                        ->label('Unit')
                        ->searchable()
                        ->getSearchResultsUsing(function (string $search) use ($userId, $isAdmin) {
                            $words = array_filter(explode(' ', strtolower(trim($search))));

                            return Unit::with(['building.street.localArea.city'])
                                ->when(! $isAdmin, fn ($q) => $q->where('user_id', $userId))
                                ->get()
                                ->filter(function ($unit) use ($words) {
                                    $label = strtolower(static::buildUnitLabel($unit));
                                    foreach ($words as $word) {
                                        if (! str_contains($label, $word)) {
                                            return false;
                                        }
                                    }
                                    return true;
                                })
                                ->take(20)
                                ->mapWithKeys(fn ($unit) => [$unit->id => static::buildUnitLabel($unit)])
                                ->toArray();
                        })
                        ->getOptionLabelUsing(function ($value) {
                            $unit = Unit::with(['building.street.localArea.city'])->find($value);
                            return $unit ? static::buildUnitLabel($unit) : $value;
                        })
                        ->required(),
                    Select::make('team_id')
                        ->label('Team')
                        ->options(fn () => Team::where(function ($q) use ($userId) {
                            $q->where('user_id', $userId)
                              ->orWhereHas('users', fn ($sub) => $sub->where('users.id', $userId));
                        })->pluck('name', 'id')->toArray())
                        ->searchable()
                        ->required(),
                    TextInput::make('rental_price')->numeric()->prefix('RM')->nullable(),
                    TextInput::make('sale_price')->numeric()->prefix('RM')->nullable(),
                    Toggle::make('is_rent_available')->label('Available for Rent'),
                    Toggle::make('is_sale_available')->label('Available for Sale'),
                    DatePicker::make('call_after')
                        ->label('Call Back After')
                        ->nullable()
                        ->hintActions(array_merge(
                            [Action::make('cb_today')->label('Today')->link()->size(Size::ExtraSmall)->action(fn (Set $set) => $set('call_after', now()->toDateString()))],
                            array_map(
                                fn (int $n) => Action::make("cb_{$n}m")->label("{$n}M")->link()->size(Size::ExtraSmall)->action(fn (Set $set) => $set('call_after', now()->addMonths($n)->subDay()->toDateString())),
                                range(1, 11)
                            ),
                            array_map(
                                fn (int $n) => Action::make("cb_{$n}y")->label("{$n}Y")->link()->size(Size::ExtraSmall)->action(fn (Set $set) => $set('call_after', now()->addYears($n)->subDay()->toDateString())),
                                range(1, 5)
                            ),
                        )),
                    FileUpload::make('media_files')
                        ->label('Photos & Videos')
                        ->multiple()
                        ->acceptedFileTypes(['image/*', 'video/*'])
                        ->disk('public')
                        ->directory('listings')
                        ->visibility('public')
                        ->reorderable()
                        ->openable()
                        ->downloadable()
                        ->maxSize(102400)
                        ->panelLayout('grid')
                        ->getUploadedFileUsing(function (string $file): ?array {
                            $disk = Storage::disk('public');

                            if (! $disk->exists($file)) {
                                return null;
                            }

                            return [
                                'name' => basename($file),
                                'size' => $disk->size($file),
                                'type' => $disk->mimeType($file) ?: 'application/octet-stream',
                                'url'  => $disk->url($file),
                            ];
                        })
                        ->columnSpanFull(),
                    Repeater::make('activities')
                        ->label('Activity History')
                        ->columnSpanFull()
                        ->schema([
                            TextInput::make('followed_up_at')->label('Date')->disabled(),
                            TextInput::make('activity_type')->label('Type')->disabled(),
                            TextInput::make('outcome_code')->label('Outcome')->disabled(),
                            TextInput::make('logged_by')->label('By')->disabled(),
                            Textarea::make('outcome_note')->label('Note')->rows(2)->disabled()->columnSpanFull(),
                        ])
                        ->columns(4)
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->defaultItems(0)
                        ->hidden(fn (Get $get) => blank($get('listing_id'))),
                    Placeholder::make('activities_page_info')
                        ->label('')
                        ->content(fn (Get $get): string => (int) ($get('activities_total') ?? 0) > 0
                            ? 'Page ' . ($get('activities_page') ?? 1) . ' of ' . max(1, (int) ceil(((int) ($get('activities_total') ?? 0)) / 3)) . ' (' . ($get('activities_total') ?? 0) . ' total)'
                            : 'No activities yet'
                        )
                        ->columnSpanFull()
                        ->hidden(fn (Get $get) => blank($get('listing_id'))),
                    Actions::make([
                        Action::make('activities_prev')
                            ->label('← Previous')
                            ->link()
                            ->action(function (Get $get, Set $set) {
                                $page = max(1, (int) ($get('activities_page') ?? 1) - 1);
                                $set('activities', static::loadActivityPage((int) $get('listing_id'), $page));
                                $set('activities_page', $page);
                            })
                            ->hidden(fn (Get $get) => (int) ($get('activities_page') ?? 1) <= 1),
                        Action::make('activities_next')
                            ->label('Next →')
                            ->link()
                            ->action(function (Get $get, Set $set) {
                                $page = (int) ($get('activities_page') ?? 1) + 1;
                                $set('activities', static::loadActivityPage((int) $get('listing_id'), $page));
                                $set('activities_page', $page);
                            })
                            ->hidden(fn (Get $get) => (int) ($get('activities_page') ?? 1) >= max(1, (int) ceil(((int) ($get('activities_total') ?? 0)) / 3))),
                    ])
                    ->columnSpanFull()
                    ->hidden(fn (Get $get) => blank($get('listing_id')) || (int) ($get('activities_total') ?? 0) <= 3),
                    Actions::make([
                        Action::make('log_activity')
                            ->label('Log Activity')
                            ->schema([
                                Select::make('activity_type')
                                    ->label('Activity Type')
                                    ->options([
                                        'call'     => 'Call',
                                        'whatsapp' => 'WhatsApp',
                                        'visit'    => 'Visit',
                                        'email'    => 'Email',
                                        'note'     => 'Note',
                                    ])
                                    ->required(),
                                Select::make('outcome_code')
                                    ->label('Outcome')
                                    ->options([
                                        'connected'      => 'Connected',
                                        'no_answer'      => 'No Answer',
                                        'busy'           => 'Busy',
                                        'voicemail'      => 'Left Voicemail',
                                        'interested'     => 'Interested',
                                        'not_interested' => 'Not Interested',
                                        'price_too_high' => 'Price Too High',
                                        'negotiating'    => 'Negotiating',
                                        'agreed'         => 'Agreed',
                                    ])
                                    ->nullable(),
                                Textarea::make('outcome_note')
                                    ->label('Note')
                                    ->rows(3)
                                    ->columnSpanFull()
                                    ->nullable(),
                                DateTimePicker::make('followed_up_at')
                                    ->label('Followed Up At')
                                    ->default(now())
                                    ->required(),
                            ])
                            ->action(function (array $data, Get $get, Set $set) {
                                $listingId = (int) $get('listing_id');

                                $activity = Activity::create([
                                    'subject_type'   => UnitListing::class,
                                    'subject_id'     => $listingId,
                                    'user_id'        => auth()->id(),
                                    'activity_type'  => $data['activity_type'],
                                    'outcome_code'   => $data['outcome_code'] ?? null,
                                    'outcome_note'   => $data['outcome_note'] ?? null,
                                    'followed_up_at' => $data['followed_up_at'],
                                ]);

                                UnitListing::where('id', $listingId)
                                    ->update(['latest_activity_id' => $activity->id]);

                                $total = Activity::where('subject_type', UnitListing::class)
                                    ->where('subject_id', $listingId)
                                    ->count();

                                $set('activities', static::loadActivityPage($listingId, 1));
                                $set('activities_page', 1);
                                $set('activities_total', $total);
                            }),
                    ])
                    ->columnSpanFull()
                    ->hidden(fn (Get $get) => blank($get('listing_id'))),
                ])
                ->reorderable(false)
                ->defaultItems(0)
                ->addActionLabel('Add Listing')
                ->afterStateHydrated(function (Repeater $component, $record) {
                    if (! $record) {
                        return;
                    }
                    $component->state(
                        $record->listings->map(function ($listing) {
                            $total = Activity::where('subject_type', UnitListing::class)
                                ->where('subject_id', $listing->id)
                                ->count();

                            return [
                                'listing_id'        => $listing->id,
                                'unit_id'           => $listing->unit_id,
                                'team_id'           => $listing->team_id,
                                'rental_price'      => $listing->rental_price,
                                'sale_price'        => $listing->sale_price,
                                'is_rent_available' => $listing->is_rent_available,
                                'is_sale_available' => $listing->is_sale_available,
                                'call_after'        => $listing->call_after?->format('Y-m-d'),
                                'media_files'       => $listing->files()->get()->pluck('path')->toArray(),
                                'activities'        => static::loadActivityPage($listing->id, 1),
                                'activities_page'   => 1,
                                'activities_total'  => $total,
                            ];
                        })->toArray()
                    );
                })
                ->dehydrated(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('ic')->label('IC / Passport')->searchable()->toggleable(),
                TextColumn::make('email')->searchable()->toggleable(),
                TextColumn::make('owner_type')->badge()->sortable(),
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

    public static function syncListingFiles(UnitListing $listing, array $paths): void
    {
        $newPaths      = array_values(array_unique(array_filter($paths)));
        $existingFiles = $listing->files()->get()->keyBy('path');

        // Detach removed files
        foreach ($existingFiles as $path => $file) {
            if (! in_array($path, $newPaths)) {
                $listing->files()->detach($file->id);

                if (! Fileable::where('file_id', $file->id)->exists()) {
                    Storage::disk($file->disk)->delete($file->path);
                    $file->delete();
                }
            }
        }

        // Attach new files or update sort_order of existing ones
        foreach ($newPaths as $index => $path) {
            if ($existingFiles->has($path)) {
                $listing->files()->updateExistingPivot($existingFiles[$path]->id, ['sort_order' => $index]);
            } else {
                $mimeType = Storage::disk('public')->mimeType($path) ?: 'application/octet-stream';
                $size     = Storage::disk('public')->exists($path) ? Storage::disk('public')->size($path) : 0;

                $file = File::create([
                    'disk'          => 'public',
                    'path'          => $path,
                    'original_name' => basename($path),
                    'mime_type'     => $mimeType,
                    'size'          => $size,
                ]);

                $listing->files()->attach($file->id, [
                    'collection' => str_starts_with($mimeType, 'video/') ? 'videos' : 'photos',
                    'sort_order' => $index,
                ]);
            }
        }
    }

    private static function loadActivityPage(int $listingId, int $page): array
    {
        return Activity::with('user')
            ->where('subject_type', UnitListing::class)
            ->where('subject_id', $listingId)
            ->orderByDesc('followed_up_at')
            ->offset(($page - 1) * 3)
            ->limit(3)
            ->get()
            ->map(fn ($a) => [
                'followed_up_at' => $a->followed_up_at?->format('d M Y, H:i') ?? '—',
                'activity_type'  => $a->activity_type,
                'outcome_code'   => $a->outcome_code ?? '—',
                'outcome_note'   => $a->outcome_note ?? '',
                'logged_by'      => $a->user?->name ?? '—',
            ])
            ->toArray();
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListOwners::route('/'),
            'create' => CreateOwner::route('/create'),
            'edit'   => EditOwner::route('/{record}/edit'),
            'import' => ImportOwnerListings::route('/import'),
        ];
    }
}
