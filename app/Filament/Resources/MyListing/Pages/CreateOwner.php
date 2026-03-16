<?php

namespace App\Filament\Resources\MyListing\Pages;

use App\Filament\Resources\MyListing\OwnerResource;
use App\Models\PhoneNumber;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateOwner extends CreateRecord
{
    protected static string $resource = OwnerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['call_after'] = now()->subDay()->toDateString();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->syncListings();
        $this->syncPhoneNumbers();
    }

    private function syncListings(): void
    {
        $items = $this->form->getRawState()['listings_data'] ?? [];

        foreach ($items as $item) {
            $listing = $this->record->listings()->create([
                'unit_id'           => $item['unit_id'] ?? null,
                'team_id'           => $item['team_id'] ?? null,
                'rental_price'      => $item['rental_price'] ?? null,
                'sale_price'        => $item['sale_price'] ?? null,
                'is_rent_available' => (bool) ($item['is_rent_available'] ?? false),
                'is_sale_available' => (bool) ($item['is_sale_available'] ?? false),
                'call_after'        => $item['call_after'] ?? null,
            ]);

            OwnerResource::syncListingFiles($listing, $item['media_files'] ?? []);
        }
    }

    private function syncPhoneNumbers(): void
    {
        $items = $this->form->getRawState()['phone_numbers_data'] ?? [];
        $duplicates = [];

        foreach ($items as $item) {
            $number = trim($item['phone_number'] ?? '');
            if (empty($number)) {
                continue;
            }

            $phone = PhoneNumber::firstOrCreate(
                ['phone_number' => $number],
                ['type' => $item['type'] ?? 'mobile']
            );

            $this->record->phoneNumbers()->attach($phone->id, ['status' => $item['status'] ?? 'need_verify']);

            $other = $phone->owners()->where('owners.id', '!=', $this->record->id)->first();
            if ($other) {
                $duplicates[] = "{$number} (Owner: {$other->name})";
            }
        }

        if (! empty($duplicates)) {
            Notification::make()
                ->title('Duplicate phone numbers detected')
                ->body('These numbers are already linked to other owners: ' . implode(', ', $duplicates))
                ->warning()
                ->persistent()
                ->send();
        }
    }
}
