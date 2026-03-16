<x-filament-panels::page>

    {{-- ── Step indicator ──────────────────────────────────────────────────── --}}
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
        @foreach(['Upload File','Map Columns','Results'] as $i => $label)
            @php $num = $i + 1; @endphp
            <x-filament::badge :color="$step === $num ? 'primary' : ($step > $num ? 'success' : 'gray')">
                {{ $num }}. {{ $label }}
            </x-filament::badge>
            @if (!$loop->last)
                <span style="color:#9ca3af;">›</span>
            @endif
        @endforeach
    </div>

    {{-- ── Step 1 : Upload ─────────────────────────────────────────────────── --}}
    @if ($step === 1)

    <x-filament::section
        heading="Upload your spreadsheet"
        description="Accepted formats: .xlsx  .xls  .csv — first row must be column headers."
    >
        <form wire:submit="uploadAndParse" style="display:flex;flex-direction:column;gap:16px;">

            {{-- Info notice --}}
            <x-filament::section icon="heroicon-o-information-circle" icon-color="primary" :contained="false">
                <p><strong>Deduplication rule:</strong> If the phone number in a row already belongs to an existing owner, that owner is reused — no duplicate is created.</p>
                <p>You can map any combination of owner and listing fields; unit columns are optional.</p>
            </x-filament::section>

            {{-- File input --}}
            <div>
                <p style="font-size:0.875rem;font-weight:500;margin-bottom:6px;">Select file</p>
                <x-filament::input.wrapper :valid="!$errors->has('uploadedFile')">
                    <input
                        type="file"
                        wire:model="uploadedFile"
                        accept=".xlsx,.xls,.csv"
                        class="fi-input"
                        style="padding:6px 12px;width:100%;cursor:pointer;"
                    >
                </x-filament::input.wrapper>
                @error('uploadedFile')
                    <p style="color:rgb(239,68,68);font-size:0.8rem;margin-top:4px;">{{ $message }}</p>
                @enderror
                <div wire:loading wire:target="uploadedFile" style="font-size:0.8rem;color:#6b7280;margin-top:4px;">
                    Uploading…
                </div>
            </div>

            {{-- No header row toggle --}}
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:0.875rem;">
                <input type="checkbox" wire:model.live="noHeaderRow" style="width:16px;height:16px;cursor:pointer;">
                <span>My file has <strong>no header row</strong> — first row is already data</span>
            </label>

            <div>
                <x-filament::button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="uploadAndParse,uploadedFile"
                >
                    <span wire:loading.remove wire:target="uploadAndParse">Upload &amp; Continue →</span>
                    <span wire:loading wire:target="uploadAndParse">Parsing…</span>
                </x-filament::button>
            </div>

        </form>
    </x-filament::section>

    @endif

    {{-- ── Step 2 : Column mapping ─────────────────────────────────────────── --}}
    @if ($step === 2)

    <x-filament::section
        heading="Map Columns"
        description="Review auto-detected mappings. Adjust any column that was not detected correctly, then click Import."
    >
        <div style="display:flex;flex-direction:column;gap:20px;">

            {{-- Dedup info --}}
            <x-filament::section icon="heroicon-o-information-circle" icon-color="primary" :contained="false">
                <p style="font-size:0.875rem;"><strong>Owner matching (automatic):</strong></p>
                <ol style="font-size:0.8rem;color:#6b7280;margin-top:4px;padding-left:18px;line-height:1.8;">
                    <li><strong>Phone match</strong> — if the phone exists, reuse that owner.</li>
                    <li><strong>Name match</strong> — if no phone match but the name matches, reuse that owner and attach the new phone.</li>
                    <li><strong>Create new</strong> — if neither matches, a new owner is created.</li>
                </ol>
            </x-filament::section>

            {{-- Default building + team --}}
            @php
                $teamOptions = $this->teamOptions();
            @endphp
            <x-filament::fieldset label="Default Building (optional)">
                <p style="font-size:0.8rem;color:#6b7280;margin-bottom:8px;">
                    Used for rows where no Building Name column is mapped or the cell is empty.
                </p>
                <x-filament::input.wrapper style="margin-bottom:6px;">
                    <x-filament::input
                        type="text"
                        wire:model.live.debounce.300ms="buildingSearch"
                        placeholder="Search buildings…"
                    />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="defaultBuildingId">
                        <option value="">— No default building —</option>
                        @foreach ($this->buildingOptions() as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
                @if ($defaultBuildingId)
                    <p style="font-size:0.75rem;color:#16a34a;margin-top:4px;">
                        ✓ {{ $this->buildingOptions()[$defaultBuildingId] ?? '' }}
                    </p>
                @endif
            </x-filament::fieldset>

            <x-filament::fieldset label="Default Team (optional)">
                <p style="font-size:0.8rem;color:#6b7280;margin-bottom:8px;">
                    Used for rows where no Team Name column is mapped or the cell is empty.
                </p>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="defaultTeamId">
                        <option value="">— No default team —</option>
                        @foreach ($teamOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </x-filament::fieldset>

            {{-- Auto-create units toggle --}}
            <label style="display:flex;align-items:flex-start;gap:8px;cursor:pointer;font-size:0.875rem;">
                <input type="checkbox" wire:model.live="autoCreateUnits" style="width:16px;height:16px;margin-top:2px;cursor:pointer;" checked>
                <span>
                    <strong>Auto-create units</strong> if not found in the database<br>
                    <span style="font-size:0.8rem;color:#6b7280;">Requires a building to be selected above or mapped in a column. The block, level, and unit number from each row will be used to create the unit automatically.</span>
                </span>
            </label>

            {{-- Column mapping table --}}
            <div>
                <p style="font-weight:600;font-size:0.875rem;margin-bottom:8px;">Column Mapping</p>
                <div style="overflow-x:auto;border:1px solid #e5e7eb;border-radius:8px;">
                    <table style="width:100%;border-collapse:collapse;font-size:0.875rem;">
                        <thead>
                            <tr style="background:#f9fafb;border-bottom:1px solid #e5e7eb;">
                                <th style="text-align:left;padding:10px 14px;font-weight:600;color:#374151;white-space:nowrap;">Excel Column</th>
                                <th style="text-align:left;padding:10px 14px;font-weight:600;color:#374151;">Sample Values</th>
                                <th style="text-align:left;padding:10px 14px;font-weight:600;color:#374151;min-width:260px;">Maps To</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($headers as $index => $header)
                            @php
                                $mapped  = !empty($columnMapping[$index]);
                                $samples = array_values(array_filter(
                                    array_map(fn($r) => trim((string)($r[$index] ?? '')), $sampleRows),
                                    fn($v) => $v !== ''
                                ));
                            @endphp
                            <tr style="border-bottom:1px solid #f3f4f6;">
                                <td style="padding:10px 14px;font-weight:500;white-space:nowrap;">
                                    @if ($mapped)
                                        <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#22c55e;margin-right:6px;vertical-align:middle;"></span>
                                    @else
                                        <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#d1d5db;margin-right:6px;vertical-align:middle;"></span>
                                    @endif
                                    {{ $header !== '' ? $header : '(Column '.($index+1).')' }}
                                </td>
                                <td style="padding:10px 14px;color:#6b7280;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                                    title="{{ implode(' | ', $samples) }}">
                                    {{ implode(' | ', array_slice($samples, 0, 3)) ?: '—' }}
                                </td>
                                <td style="padding:8px 14px;">
                                    <x-filament::input.wrapper>
                                        <x-filament::input.select wire:model.live="columnMapping.{{ $index }}">
                                            @foreach ($this->availableFields() as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </x-filament::input.select>
                                    </x-filament::input.wrapper>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Mapped summary --}}
            @php
                $mappedList = collect($columnMapping)->filter()
                    ->map(fn($f) => Str::before($this->availableFields()[$f] ?? $f, '  ('))
                    ->values();
            @endphp
            @if ($mappedList->isNotEmpty())
            <div style="display:flex;flex-wrap:wrap;gap:6px;align-items:center;">
                <span style="font-size:0.8rem;color:#6b7280;font-weight:500;">Fields to import:</span>
                @foreach ($mappedList as $lbl)
                    <x-filament::badge color="primary">{{ $lbl }}</x-filament::badge>
                @endforeach
            </div>
            @endif

            {{-- Actions --}}
            <div style="display:flex;gap:10px;padding-top:4px;">
                <x-filament::button color="gray" wire:click="goBack">
                    ← Back
                </x-filament::button>
                <x-filament::button
                    wire:click="import"
                    wire:loading.attr="disabled"
                    wire:target="import"
                >
                    <span wire:loading.remove wire:target="import">Import →</span>
                    <span wire:loading wire:target="import">Importing… please wait</span>
                </x-filament::button>
            </div>

        </div>
    </x-filament::section>

    @endif

    {{-- ── Step 3 : Results ────────────────────────────────────────────────── --}}
    @if ($step === 3)

    <x-filament::section heading="Import Complete">
        <div style="display:flex;flex-direction:column;gap:20px;">

            {{-- Summary cards --}}
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;">
                <x-filament::section :contained="false">
                    <div style="text-align:center;">
                        <div style="font-size:2rem;font-weight:700;color:#16a34a;">{{ $createdOwners }}</div>
                        <div style="font-size:0.8rem;color:#6b7280;margin-top:2px;">New Owners</div>
                    </div>
                </x-filament::section>
                <x-filament::section :contained="false">
                    <div style="text-align:center;">
                        <div style="font-size:2rem;font-weight:700;color:#2563eb;">{{ $reusedOwners }}</div>
                        <div style="font-size:0.8rem;color:#6b7280;margin-top:2px;">Owners Reused</div>
                    </div>
                </x-filament::section>
                <x-filament::section :contained="false">
                    <div style="text-align:center;">
                        <div style="font-size:2rem;font-weight:700;color:#16a34a;">{{ $createdListings }}</div>
                        <div style="font-size:0.8rem;color:#6b7280;margin-top:2px;">Listings Created</div>
                    </div>
                </x-filament::section>
                <x-filament::section :contained="false">
                    <div style="text-align:center;">
                        <div style="font-size:2rem;font-weight:700;color:#d97706;">{{ $skippedRows }}</div>
                        <div style="font-size:0.8rem;color:#6b7280;margin-top:2px;">Skipped / Owner-only</div>
                    </div>
                </x-filament::section>
            </div>

            {{-- Actions --}}
            <div style="display:flex;gap:10px;">
                <x-filament::button color="gray" wire:click="resetWizard">
                    Import Another File
                </x-filament::button>
                <x-filament::button
                    tag="a"
                    href="{{ \App\Filament\Resources\MyListing\OwnerResource::getUrl('index') }}"
                >
                    Go to Owners →
                </x-filament::button>
            </div>

        </div>
    </x-filament::section>

    {{-- Row details --}}
    @if (!empty($results))
    <x-filament::section heading="Row-by-row details">
        <div style="overflow-x:auto;border:1px solid #e5e7eb;border-radius:8px;">
            <table style="width:100%;border-collapse:collapse;font-size:0.875rem;">
                <thead>
                    <tr style="background:#f9fafb;border-bottom:1px solid #e5e7eb;">
                        <th style="text-align:left;padding:8px 14px;font-weight:600;color:#374151;">#</th>
                        <th style="text-align:left;padding:8px 14px;font-weight:600;color:#374151;">Owner</th>
                        <th style="text-align:left;padding:8px 14px;font-weight:600;color:#374151;">Status</th>
                        <th style="text-align:left;padding:8px 14px;font-weight:600;color:#374151;">Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($results as $result)
                    <tr style="border-bottom:1px solid #f3f4f6;">
                        <td style="padding:8px 14px;color:#9ca3af;font-family:monospace;font-size:0.8rem;">{{ $result['row'] }}</td>
                        <td style="padding:8px 14px;font-weight:500;">{{ $result['owner'] ?? '—' }}</td>
                        <td style="padding:8px 14px;">
                            @if ($result['status'] === 'imported')
                                <x-filament::badge color="success">Imported</x-filament::badge>
                            @elseif ($result['status'] === 'owner_only')
                                <x-filament::badge color="primary">Owner only</x-filament::badge>
                            @else
                                <x-filament::badge color="warning">Skipped</x-filament::badge>
                            @endif
                        </td>
                        <td style="padding:8px 14px;color:#6b7280;font-size:0.8rem;">
                            @if ($result['status'] === 'imported' || $result['status'] === 'owner_only')
                                @if ($result['owner_created'] ?? false)
                                    New owner created
                                @elseif (($result['match_reason'] ?? '') === 'name')
                                    Existing owner reused (matched by name) — new phone added
                                @else
                                    Existing owner reused (matched by phone)
                                @endif
                                @if ($result['status'] === 'owner_only')
                                    — {{ $result['reason'] ?? '' }}
                                @endif
                            @else
                                {{ $result['reason'] ?? '' }}
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>
    @endif

    @endif

</x-filament-panels::page>
