<?php

namespace App\Filament\Resources\MyListing\Pages;

use App\Filament\Resources\MyListing\OwnerResource;
use App\Models\Building;
use App\Models\Owner;
use App\Models\PhoneNumber;
use App\Models\Team;
use App\Models\Unit;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportOwnerListings extends Page
{
    use WithFileUploads;

    protected static string $resource = OwnerResource::class;
    protected string $view             = 'filament.resources.my-listing.pages.import-owner-listings';
    protected static ?string $title    = 'Import Owners & Listings';

    // ── Livewire state ────────────────────────────────────────────────────────

    public int $step = 1;

    /** @var mixed Livewire temporary upload */
    public $uploadedFile = null;

    /** Stored file path (relative to storage/app/) */
    public ?string $tempFilePath = null;
    public string  $fileExtension = '';

    /** True when the file has no header row — all rows are data */
    public bool $noHeaderRow = false;

    /** Auto-create units in the DB when a matching unit cannot be found */
    public bool $autoCreateUnits = true;

    /** @var list<string> */
    public array $headers = [];

    /** @var list<list<mixed>> first 5 data rows */
    public array $sampleRows = [];

    /** @var array<int, string> colIndex => field key */
    public array $columnMapping = [];

    /** Default building selected by the user in the mapping step (optional) */
    public ?int $defaultBuildingId = null;

    /** Search term for filtering the building dropdown */
    public string $buildingSearch = '';

    /** Default team selected by the user in the mapping step (optional) */
    public ?int $defaultTeamId = null;


    /** @var list<array<string, mixed>> */
    public array $results = [];

    public int $createdOwners  = 0;
    public int $reusedOwners   = 0;
    public int $createdListings = 0;
    public int $skippedRows    = 0;

    // ── Field definitions ─────────────────────────────────────────────────────

    public static function availableFields(): array
    {
        return [
            ''                  => '— Skip Column —',
            'owner_name'        => 'Owner Name',
            'ic'                => 'IC / Passport',
            'email'             => 'Email',
            'mailing_address'   => 'Mailing Address',
            'owner_type'        => 'Owner Type  (individual / company / government)',
            'phone_number'      => 'Phone Number',
            'phone_status'      => 'Phone Status  (need_verify / active / primary / …)',
            'building_name'     => 'Building Name',
            'unit_combined'     => 'Unit — combined  (Block-Level-Unit, e.g. A-10-5)',
            'unit_block'        => 'Block',
            'unit_level'        => 'Level',
            'unit_number'       => 'Unit Number',
            'team_name'         => 'Team Name',
            'rental_price'      => 'Rental Price',
            'sale_price'        => 'Sale Price',
            'is_rent_available' => 'Available for Rent  (yes / no / 1 / 0)',
            'is_sale_available' => 'Available for Sale  (yes / no / 1 / 0)',
            'call_after'        => 'Call Back After  (date)',
        ];
    }

    public function buildingOptions(): array
    {
        $search = trim($this->buildingSearch);

        return Building::with(['street.localArea.city'])
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function (Building $b) {
                $parts = array_filter([
                    $b->street?->name,
                    $b->street?->localArea?->name,
                    $b->street?->localArea?->city?->name,
                ]);
                $label = $b->name . (! empty($parts) ? ', ' . implode(', ', $parts) : '');
                return [$b->id => $label];
            })
            ->toArray();
    }

    public function teamOptions(): array
    {
        $userId = auth()->id();

        return Team::where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
              ->orWhereHas('users', fn ($sub) => $sub->where('users.id', $userId));
        })->orderBy('name')->pluck('name', 'id')->toArray();
    }

    // ── Step 1 : Upload ───────────────────────────────────────────────────────

    public function uploadAndParse(): void
    {
        $this->validate([
            'uploadedFile' => 'required|file|mimes:xlsx,xls,csv|max:20480',
        ]);

        $this->fileExtension = strtolower($this->uploadedFile->getClientOriginalExtension());

        // Persist the file so it survives re-renders
        $filename = 'import_' . uniqid() . '.' . $this->fileExtension;
        Storage::disk('local')->makeDirectory('imports');
        $this->uploadedFile->storeAs('imports', $filename, 'local');
        // Storage::path() gives correct root (storage/app/private on Laravel 11).
        // Normalize separators so PhpSpreadsheet works on Windows.
        $this->tempFilePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, Storage::disk('local')->path('imports/' . $filename));

        [$this->headers, $this->sampleRows] = $this->readHeadersAndSample($this->tempFilePath, $this->fileExtension);

        if (empty($this->headers)) {
            Notification::make()->title('File appears empty or invalid')->danger()->send();
            return;
        }

        // Auto-detect column mappings
        $this->columnMapping = [];
        foreach ($this->headers as $index => $header) {
            $this->columnMapping[$index] = $this->autoDetectField(strtolower(trim((string) $header)));
        }

        $this->step = 2;
    }

    // ── Step 2 : Column mapping (just UI changes, import fires next) ──────────

    public function goBack(): void
    {
        $this->step = 1;
        $this->cleanupTempFile();
    }

    // ── Step 3 : Import ───────────────────────────────────────────────────────

    public function import(): void
    {
        $mappedFields = array_values($this->columnMapping);

        if (! in_array('owner_name', $mappedFields)) {
            Notification::make()
                ->title('Owner Name column required')
                ->body('Please map at least one column to "Owner Name" before importing.')
                ->danger()
                ->send();
            return;
        }

        if (! $this->tempFilePath || ! file_exists($this->tempFilePath)) {
            Notification::make()->title('Uploaded file no longer available — please re-upload.')->danger()->send();
            $this->step = 1;
            return;
        }

        $allRows = $this->readAllDataRows($this->tempFilePath, $this->fileExtension);

        $userId               = auth()->id();
        $this->results        = [];
        $this->createdOwners  = 0;
        $this->reusedOwners   = 0;
        $this->createdListings = 0;
        $this->skippedRows    = 0;

        foreach ($allRows as $rowIndex => $row) {
            // Map row values to named fields
            $data = [];
            foreach ($this->columnMapping as $colIndex => $fieldName) {
                if (! empty($fieldName)) {
                    $data[$fieldName] = isset($row[$colIndex]) ? (string) $row[$colIndex] : null;
                }
            }

            if (empty(array_filter($data, fn ($v) => $v !== null && $v !== ''))) {
                continue;
            }

            $result          = $this->processRow($data, $userId, $rowIndex + 2); // +2: header row + 0-index
            $this->results[] = $result;

            if ($result['status'] === 'imported') {
                $this->createdListings++;
                $result['owner_created'] ? $this->createdOwners++ : $this->reusedOwners++;
            } elseif ($result['status'] === 'owner_only') {
                $result['owner_created'] ? $this->createdOwners++ : $this->reusedOwners++;
            } else {
                $this->skippedRows++;
            }
        }

        $this->cleanupTempFile();
        $this->step = 3;
    }

    public function resetWizard(): void
    {
        $this->cleanupTempFile();
        $this->step              = 1;
        $this->uploadedFile      = null;
        $this->defaultBuildingId  = null;
        $this->defaultTeamId      = null;
        $this->noHeaderRow        = false;
        $this->autoCreateUnits    = true;
        $this->headers           = [];
        $this->sampleRows     = [];
        $this->columnMapping  = [];
        $this->results        = [];
        $this->createdOwners  = 0;
        $this->reusedOwners   = 0;
        $this->createdListings = 0;
        $this->skippedRows    = 0;
    }

    // ── Core import logic (mirrors CreateOwner / EditOwner algorithm) ─────────

    private function processRow(array $data, int $userId, int $rowNum): array
    {
        $ownerName   = trim($data['owner_name'] ?? '');
        $phoneNumber = trim($data['phone_number'] ?? '');

        if ($ownerName === '') {
            return ['row' => $rowNum, 'status' => 'skipped', 'reason' => 'Owner name is empty', 'owner' => '—'];
        }

        // ── Resolve phone status once (shared by all branches) ───────────────
        $validStatuses = ['need_verify', 'active', 'primary', 'inactive', 'disconnected', 'wrong_number'];
        $phoneStatus   = strtolower(trim($data['phone_status'] ?? 'need_verify'));
        if (! in_array($phoneStatus, $validStatuses)) {
            $phoneStatus = 'need_verify';
        }

        // ── Smart dedup: phone → name → create ───────────────────────────────
        $owner        = null;
        $ownerCreated = false;
        $matchReason  = null;

        // 1) Match by phone number (same algorithm as the Filament form)
        if ($phoneNumber !== '') {
            $existingPhone = PhoneNumber::where('phone_number', $phoneNumber)->first();
            if ($existingPhone) {
                $owner       = $existingPhone->owners()->first();
                $matchReason = 'phone';
            }
        }

        // 2) Match by name if phone found no one
        if (! $owner && $ownerName !== '') {
            $owner = Owner::whereRaw('LOWER(TRIM(name)) = ?', [strtolower($ownerName)])->first();
            if ($owner) {
                $matchReason = 'name';
                // Attach the new phone to the existing owner (if not already there)
                if ($phoneNumber !== '') {
                    $phone = PhoneNumber::firstOrCreate(
                        ['phone_number' => $phoneNumber],
                        ['type' => 'mobile']
                    );
                    if (! $owner->phoneNumbers()->where('phone_numbers.id', $phone->id)->exists()) {
                        $owner->phoneNumbers()->attach($phone->id, ['status' => $phoneStatus]);
                    }
                }
            }
        }

        // 3) Create new owner — mirrors CreateOwner::afterCreate()
        if (! $owner) {
            $ownerType = strtolower(trim($data['owner_type'] ?? 'individual'));
            if (! in_array($ownerType, ['individual', 'company', 'government'])) {
                $ownerType = 'individual';
            }

            $owner = Owner::create([
                'user_id'         => $userId,
                'name'            => $ownerName,
                'ic'              => ($data['ic'] ?? '') !== '' ? $data['ic'] : null,
                'email'           => ($data['email'] ?? '') !== '' ? $data['email'] : null,
                'mailing_address' => ($data['mailing_address'] ?? '') !== '' ? $data['mailing_address'] : null,
                'owner_type'      => $ownerType,
            ]);
            $ownerCreated = true;

            if ($phoneNumber !== '') {
                $phone = PhoneNumber::firstOrCreate(
                    ['phone_number' => $phoneNumber],
                    ['type' => 'mobile']
                );
                $owner->phoneNumbers()->attach($phone->id, ['status' => $phoneStatus]);
            }
        }

        // ── Resolve unit (listing is optional if no unit mapped) ──────────────
        $unitId = $this->resolveUnit($data, $userId);

        if ($unitId === null) {
            return [
                'row'           => $rowNum,
                'status'        => 'owner_only',
                'owner'         => $owner->name,
                'owner_created' => $ownerCreated,
                'match_reason'  => $matchReason,
                'reason'        => 'No unit matched — owner saved, listing skipped',
            ];
        }

        // ── Resolve team ──────────────────────────────────────────────────────
        $teamId = $this->resolveTeam($data, $userId);

        // ── Parse optional fields ─────────────────────────────────────────────
        $callAfter = null;
        if (! empty($data['call_after'])) {
            try {
                $callAfter = Carbon::parse($data['call_after'])->format('Y-m-d');
            } catch (\Exception) {
                $callAfter = null;
            }
        }

        // ── Create listing — mirrors syncListings() in CreateOwner ────────────
        $listing = $owner->listings()->create([
            'unit_id'           => $unitId,
            'team_id'           => $teamId,
            'rental_price'      => is_numeric($data['rental_price'] ?? '') ? $data['rental_price'] : null,
            'sale_price'        => is_numeric($data['sale_price'] ?? '') ? $data['sale_price'] : null,
            'is_rent_available' => $this->parseBool($data['is_rent_available'] ?? null),
            'is_sale_available' => $this->parseBool($data['is_sale_available'] ?? null),
            'call_after'        => $callAfter,
        ]);

        return [
            'row'           => $rowNum,
            'status'        => 'imported',
            'owner'         => $owner->name,
            'owner_created' => $ownerCreated,
            'match_reason'  => $matchReason,
            'listing_id'    => $listing->id,
        ];
    }

    // ── Unit resolution ───────────────────────────────────────────────────────

    private function resolveUnit(array $data, int $userId): ?int
    {
        $buildingName = trim($data['building_name'] ?? '');
        $block        = trim($data['unit_block'] ?? '');
        $level        = trim($data['unit_level'] ?? '');
        $unitNumber   = trim($data['unit_number'] ?? '');

        // Auto-split combined "Block-Level-Unit" values (e.g. "A-05-01")
        $combined = trim($data['unit_combined'] ?? '');
        if ($combined !== '') {
            [$block, $level, $unitNumber] = $this->splitCombinedUnit($combined);
        } elseif ($unitNumber !== '' && str_contains($unitNumber, '-') && $block === '' && $level === '') {
            [$block, $level, $unitNumber] = $this->splitCombinedUnit($unitNumber);
        }

        // Nothing to work with
        if ($buildingName === '' && $unitNumber === '' && $block === '' && $level === '' && ! $this->defaultBuildingId) {
            return null;
        }

        $isAdmin    = auth()->user()->role === 'admin';
        $buildingId = $this->resolveBuildingId($buildingName);

        $query = Unit::query()
            ->when(! $isAdmin, fn ($q) => $q->where('user_id', $userId));

        if ($buildingId) {
            $query->where('building_id', $buildingId);
        } elseif ($buildingName !== '') {
            $query->whereHas('building', fn ($q) => $q->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($buildingName)]));
        }

        if ($block !== '') {
            $query->where('block', $block);
        }
        if ($level !== '') {
            $query->where('level', $level);
        }
        if ($unitNumber !== '') {
            $query->where('unit_number', $unitNumber);
        }

        $unitId = $query->value('id');

        if ($unitId) {
            return $unitId;
        }

        // Auto-create the unit when it doesn't exist yet
        if ($this->autoCreateUnits && $unitNumber !== '' && $buildingId) {
            return Unit::create([
                'user_id'     => $userId,
                'building_id' => $buildingId,
                'block'       => $block !== '' ? $block : null,
                'level'       => $level !== '' ? $level : null,
                'unit_number' => $unitNumber,
            ])->id;
        }

        return null;
    }

    private function resolveBuildingId(string $buildingName = ''): ?int
    {
        if ($buildingName !== '') {
            return Building::whereRaw('LOWER(TRIM(name)) = ?', [strtolower($buildingName)])->value('id');
        }

        return $this->defaultBuildingId ?: null;
    }

    /**
     * Split "A-10-5" → ['A','10','5']
     *        "10-5"  → ['','10','5']
     *        "5"     → ['','','5']
     *
     * @return array{0:string, 1:string, 2:string}  [block, level, unit]
     */
    private function splitCombinedUnit(string $value): array
    {
        $parts = array_values(array_filter(
            array_map('trim', explode('-', $value)),
            fn ($p) => $p !== ''
        ));

        return match (count($parts)) {
            0       => ['', '', ''],
            1       => ['', '', $parts[0]],
            2       => ['', $parts[0], $parts[1]],
            default => [$parts[0], $parts[1], $parts[2]],   // 3 or more: take first 3
        };
    }

    // ── Team resolution ───────────────────────────────────────────────────────

    private function resolveTeam(array $data, int $userId): ?int
    {
        $teamName = trim($data['team_name'] ?? '');

        if ($teamName !== '') {
            return Team::where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhereHas('users', fn ($sub) => $sub->where('users.id', $userId));
            })->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($teamName)])->value('id');
        }

        // Fall back to the default team selected in the mapping step
        return $this->defaultTeamId ?: null;
    }

    // ── File parsing helpers ──────────────────────────────────────────────────

    private function readHeadersAndSample(string $path, string $ext): array
    {
        $rows = $this->readRawRows($path, $ext, limit: 6);

        if (empty($rows)) {
            return [[], []];
        }

        if ($this->noHeaderRow) {
            // Synthesise headers: Column 1, Column 2, …
            $colCount = max(array_map('count', $rows));
            $headers  = array_map(fn ($i) => 'Column ' . ($i + 1), range(0, $colCount - 1));
            return [$headers, array_slice($rows, 0, 5)]; // all rows are data
        }

        $headers    = array_map(fn ($v) => (string) ($v ?? ''), $rows[0]);
        $sampleRows = array_slice($rows, 1, 5);

        return [$headers, $sampleRows];
    }

    private function readAllDataRows(string $path, string $ext): array
    {
        $rows = $this->readRawRows($path, $ext);
        return $this->noHeaderRow ? $rows : array_slice($rows, 1);
    }

    private function readRawRows(string $path, string $ext, ?int $limit = null): array
    {
        if ($ext === 'csv') {
            return $this->readCsv($path, $limit);
        }

        return $this->readSpreadsheet($path, $limit);
    }

    private function readCsv(string $path, ?int $limit): array
    {
        $rows   = [];
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return [];
        }

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
            if ($limit !== null && count($rows) >= $limit) {
                break;
            }
        }

        fclose($handle);
        return $rows;
    }

    private function readSpreadsheet(string $path, ?int $limit): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet       = $spreadsheet->getActiveSheet();
        $rows        = [];

        foreach ($sheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            $rowData = [];
            foreach ($cellIterator as $cell) {
                $rowData[] = $cell->getFormattedValue();
            }
            $rows[] = $rowData;

            if ($limit !== null && count($rows) >= $limit) {
                break;
            }
        }

        return $rows;
    }

    // ── Auto-detect column field ──────────────────────────────────────────────

    private function autoDetectField(string $header): string
    {
        return match (true) {
            str_contains($header, 'owner') && str_contains($header, 'name')             => 'owner_name',
            $header === 'name'                                                           => 'owner_name',
            str_contains($header, 'ic') || str_contains($header, 'nric') || str_contains($header, 'passport') => 'ic',
            str_contains($header, 'email')                                              => 'email',
            str_contains($header, 'mailing') || str_contains($header, 'address')        => 'mailing_address',
            $header === 'owner_type' || $header === 'type'                              => 'owner_type',
            str_contains($header, 'phone status') || str_contains($header, 'phone_status') => 'phone_status',
            str_contains($header, 'phone') || str_contains($header, 'mobile')
                || str_contains($header, 'contact') || str_contains($header, 'tel')     => 'phone_number',
            str_contains($header, 'building') || str_contains($header, 'project')       => 'building_name',
            str_contains($header, 'block') && str_contains($header, 'level')            => 'unit_combined',
            str_contains($header, 'block') && str_contains($header, 'unit')             => 'unit_combined',
            str_contains($header, 'block')                                              => 'unit_block',
            str_contains($header, 'level') || str_contains($header, 'floor')            => 'unit_level',
            str_contains($header, 'unit')                                               => 'unit_number',
            str_contains($header, 'team')                                               => 'team_name',
            str_contains($header, 'rent') && str_contains($header, 'avail')             => 'is_rent_available',
            str_contains($header, 'sale') && str_contains($header, 'avail')             => 'is_sale_available',
            str_contains($header, 'rental') || (str_contains($header, 'rent') && str_contains($header, 'price')) => 'rental_price',
            str_contains($header, 'sale') && str_contains($header, 'price')             => 'sale_price',
            str_contains($header, 'call') || str_contains($header, 'callback')          => 'call_after',
            default                                                                     => '',
        };
    }

    private function parseBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower(trim((string) $value)), ['yes', 'true', '1', 'y', 'on']);
    }

    private function cleanupTempFile(): void
    {
        if ($this->tempFilePath && file_exists($this->tempFilePath)) {
            @unlink($this->tempFilePath);
        }
        $this->tempFilePath = null;
    }
}
