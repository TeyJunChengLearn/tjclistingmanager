<?php

namespace App\Filament\Resources\MyListing\Pages;

use App\Filament\Resources\MyListing\OwnerResource;
use App\Models\Owner;
use App\Models\OwnerDuplicateDecision;
use App\Models\PhoneNumber;
use App\Models\UnitListing;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class EditOwner extends EditRecord
{
    protected static string $resource = OwnerResource::class;

    /** @var array<int, array<string, mixed>> */
    public array $duplicates = [];

    /** @var array<int, array<string, mixed>> */
    public array $phoneConflicts = [];

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    // -------------------------------------------------------------------------
    // Lifecycle hooks
    // -------------------------------------------------------------------------

    protected function afterFill(): void
    {
        $this->record->load('phoneNumbers', 'listings');
        $this->detectDuplicates();
        $this->detectPhoneConflicts();
    }

    protected function afterSave(): void
    {
        $this->syncListings();
        $this->syncPhoneNumbers();
        $this->record->load('phoneNumbers', 'listings');
        $this->detectDuplicates();
        $this->detectPhoneConflicts();
    }

    // -------------------------------------------------------------------------
    // Duplicate detection
    // Criteria: same name (case-insensitive) AND at least one shared phone number
    // -------------------------------------------------------------------------

    private function detectDuplicates(): void
    {
        $phoneIds = $this->record->phoneNumbers->pluck('id')->toArray();

        // Collect IDs of owners already dismissed as "different" (bidirectional)
        $dismissedIds = OwnerDuplicateDecision::where(function ($q) {
            $q->where('owner_id_1', $this->record->id)
              ->orWhere('owner_id_2', $this->record->id);
        })->get()->map(fn ($d) =>
            $d->owner_id_1 === $this->record->id ? $d->owner_id_2 : $d->owner_id_1
        )->toArray();

        $this->duplicates = Owner::with('phoneNumbers')
            ->withCount('listings')
            ->where('id', '!=', $this->record->id)
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($this->record->name))])
            ->whereNotIn('id', $dismissedIds)
            ->get()
            ->map(function ($owner) use ($phoneIds) {
                $ownerPhoneIds = $owner->phoneNumbers->pluck('id')->toArray();
                $hasSharedPhone = ! empty($phoneIds) && ! empty(array_intersect($phoneIds, $ownerPhoneIds));

                return [
                    'id'             => $owner->id,
                    'name'           => $owner->name,
                    'ic'             => $owner->ic,
                    'phone_numbers'  => $owner->phoneNumbers->pluck('phone_number')->toArray(),
                    'listings_count' => $owner->listings_count,
                    'match_type'     => $hasSharedPhone ? 'name_and_phone' : 'name_only',
                ];
            })
            ->toArray();
    }

    private function detectPhoneConflicts(): void
    {
        $phoneIds = $this->record->phoneNumbers->pluck('id')->toArray();

        if (empty($phoneIds)) {
            $this->phoneConflicts = [];
            return;
        }

        $this->phoneConflicts = Owner::with(['phoneNumbers', 'listings.unit.building'])
            ->where('id', '!=', $this->record->id)
            ->whereRaw('LOWER(TRIM(name)) != ?', [strtolower(trim($this->record->name))])
            ->whereHas('phoneNumbers', fn ($q) => $q->whereIn('phone_numbers.id', $phoneIds))
            ->get()
            ->map(fn ($owner) => [
                'id'             => $owner->id,
                'name'           => $owner->name,
                'ic'             => $owner->ic,
                'shared_numbers' => $owner->phoneNumbers
                    ->whereIn('id', $phoneIds)
                    ->pluck('phone_number')
                    ->toArray(),
                'listings'       => $owner->listings->map(fn ($listing) => [
                    'unit'     => implode('-', array_filter([
                        $listing->unit?->block,
                        $listing->unit?->level,
                        $listing->unit?->unit_number,
                    ])) ?: '—',
                    'building' => $listing->unit?->building?->name ?? '—',
                ])->toArray(),
            ])
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // Livewire actions called from the duplicates panel
    // -------------------------------------------------------------------------

    /**
     * Merge the duplicate owner into this record:
     * 1. Reassign all listings (including soft-deleted) to this owner
     * 2. Transfer phone numbers not already on this owner
     * 3. Soft-delete the duplicate
     * 4. Refresh the form
     */
    public function mergeOwner(int $duplicateId): void
    {
        $duplicate = Owner::with('phoneNumbers')->withTrashed()->find($duplicateId);

        if (! $duplicate) {
            return;
        }

        // 1. Reassign all listings (including soft-deleted) to current owner
        UnitListing::withTrashed()
            ->where('owner_id', $duplicateId)
            ->update(['owner_id' => $this->record->id]);

        // 2. Transfer unique phone numbers from duplicate to current owner
        $currentPhoneIds = $this->record->phoneNumbers->pluck('id')->toArray();

        foreach ($duplicate->phoneNumbers as $phone) {
            $duplicate->phoneNumbers()->detach($phone->id);

            if (! in_array($phone->id, $currentPhoneIds)) {
                $this->record->phoneNumbers()->attach($phone->id, ['status' => 'need_verify']);
            }
        }

        // 3. Soft-delete the now-empty duplicate owner
        $duplicate->delete();

        // 4. Reload phone numbers so the form and duplicate detection see fresh data
        $this->record->load('phoneNumbers');
        $this->fillForm();

        Notification::make()
            ->title('Owners merged successfully')
            ->body('All listings and phone numbers have been transferred and the duplicate was removed.')
            ->success()
            ->send();
    }

    /**
     * Persist a "different owner" decision so this pair is never flagged again.
     * The pair is stored with the smaller ID first to ensure uniqueness.
     */
    public function dismissOwner(int $duplicateId): void
    {
        OwnerDuplicateDecision::firstOrCreate([
            'owner_id_1' => min($this->record->id, $duplicateId),
            'owner_id_2' => max($this->record->id, $duplicateId),
        ]);

        $this->detectDuplicates();

        Notification::make()
            ->title('Marked as different owners')
            ->info()
            ->send();
    }

    // -------------------------------------------------------------------------
    // Override content to inject the duplicates panel below the form
    // -------------------------------------------------------------------------

    public function content(Schema $schema): Schema
    {
        if ($this->hasCombinedRelationManagerTabsWithContent()) {
            return $schema->components([
                $this->getRelationManagersContentComponent(),
            ]);
        }

        return $schema->components([
            $this->getFormContentComponent(),
            $this->getRelationManagersContentComponent(),
            View::make('filament.resources.my-listing.pages.owner-phone-conflicts')
                ->viewData(['livewire' => $this]),
            View::make('filament.resources.my-listing.pages.owner-duplicates')
                ->viewData(['livewire' => $this]),
        ]);
    }

    // -------------------------------------------------------------------------
    // Private sync helpers (unchanged from original)
    // -------------------------------------------------------------------------

    private function syncListings(): void
    {
        $items = $this->form->getRawState()['listings_data'] ?? [];

        $incomingIds = array_values(array_filter(array_column($items, 'listing_id')));

        if (empty($incomingIds)) {
            $this->record->listings()->delete();
        } else {
            $this->record->listings()->whereNotIn('id', $incomingIds)->delete();
        }

        foreach ($items as $item) {
            $data = [
                'unit_id'           => $item['unit_id'] ?? null,
                'team_id'           => $item['team_id'] ?? null,
                'rental_price'      => $item['rental_price'] ?? null,
                'sale_price'        => $item['sale_price'] ?? null,
                'is_rent_available' => (bool) ($item['is_rent_available'] ?? false),
                'is_sale_available' => (bool) ($item['is_sale_available'] ?? false),
                'call_after'        => $item['call_after'] ?? null,
            ];

            if (! empty($item['listing_id'])) {
                $this->record->listings()->where('id', $item['listing_id'])->update($data);
                $listing = UnitListing::find($item['listing_id']);
            } else {
                $listing = $this->record->listings()->create($data);
            }

            if ($listing) {
                OwnerResource::syncListingFiles($listing, $item['media_files'] ?? []);
            }
        }
    }

    private function syncPhoneNumbers(): void
    {
        $items = $this->form->getRawState()['phone_numbers_data'] ?? [];

        $existingPhoneIds = $this->record->phoneNumbers()->pluck('phone_numbers.id')->toArray();
        $newPhoneIds = [];

        foreach ($items as $item) {
            $number = trim($item['phone_number'] ?? '');
            if (empty($number)) {
                continue;
            }

            $phone = PhoneNumber::firstOrCreate(
                ['phone_number' => $number],
                ['type' => $item['type'] ?? 'mobile']
            );

            $newPhoneIds[] = $phone->id;

            $status = $item['status'] ?? 'need_verify';

            if (! in_array($phone->id, $existingPhoneIds)) {
                $this->record->phoneNumbers()->attach($phone->id, ['status' => $status]);
            } else {
                $this->record->phoneNumbers()->updateExistingPivot($phone->id, ['status' => $status]);
            }
        }

        $toDetach = array_diff($existingPhoneIds, $newPhoneIds);
        if (! empty($toDetach)) {
            $this->record->phoneNumbers()->detach($toDetach);
        }
    }
}
