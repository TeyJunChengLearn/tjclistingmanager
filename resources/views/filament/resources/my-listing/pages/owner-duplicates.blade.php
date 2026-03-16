@if (!empty($livewire->duplicates))
<div style="margin-top:1rem">
    <div style="background:#fff;border-radius:.75rem;box-shadow:0 1px 3px 0 rgb(0 0 0/.1);border:1px solid #e5e7eb">

        {{-- Header --}}
        <div style="display:flex;align-items:flex-start;gap:.75rem;padding:1rem 1.5rem;border-bottom:1px solid #fcd34d;background:#fffbeb;border-radius:.75rem .75rem 0 0">
            <div style="flex-shrink:0;margin-top:2px;color:#d97706">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="20" height="20">
                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                </svg>
            </div>
            <div>
                <p style="margin:0;font-size:.875rem;font-weight:600;color:#92400e">
                    Potential Duplicate Owner{{ count($livewire->duplicates) > 1 ? 's' : '' }} Found
                </p>
                <p style="margin:.25rem 0 0;font-size:.75rem;color:#b45309">
                    The following owner{{ count($livewire->duplicates) > 1 ? 's share' : ' shares' }}
                    the same name. Please confirm whether {{ count($livewire->duplicates) > 1 ? 'they refer' : 'it refers' }} to the same person.
                </p>
            </div>
        </div>

        {{-- Table --}}
        <div style="padding:1rem 1.5rem;overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:.875rem">
                <thead>
                    <tr style="border-bottom:1px solid #e5e7eb;text-align:left">
                        <th style="padding-bottom:.625rem;padding-right:1rem;font-size:.75rem;font-weight:500;color:#6b7280">Name</th>
                        <th style="padding-bottom:.625rem;padding-right:1rem;font-size:.75rem;font-weight:500;color:#6b7280">IC / Passport</th>
                        <th style="padding-bottom:.625rem;padding-right:1rem;font-size:.75rem;font-weight:500;color:#6b7280">Phone Numbers</th>
                        <th style="padding-bottom:.625rem;padding-right:1rem;font-size:.75rem;font-weight:500;color:#6b7280">Listings</th>
                        <th style="padding-bottom:.625rem;padding-right:1rem;font-size:.75rem;font-weight:500;color:#6b7280">Matched By</th>
                        <th style="padding-bottom:.625rem;font-size:.75rem;font-weight:500;color:#6b7280">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($livewire->duplicates as $dup)
                        <tr style="border-bottom:1px solid #f3f4f6">
                            <td style="padding:.75rem 1rem .75rem 0;font-weight:500;color:#111827">
                                {{ $dup['name'] }}
                            </td>
                            <td style="padding:.75rem 1rem .75rem 0;color:#4b5563">
                                {{ $dup['ic'] ?? '—' }}
                            </td>
                            <td style="padding:.75rem 1rem .75rem 0;color:#4b5563">
                                {{ implode(', ', $dup['phone_numbers']) ?: '—' }}
                            </td>
                            <td style="padding:.75rem 1rem .75rem 0;color:#4b5563">
                                {{ $dup['listings_count'] }}
                            </td>
                            <td style="padding:.75rem 1rem .75rem 0">
                                @if ($dup['match_type'] === 'name_and_phone')
                                    <span style="display:inline-flex;align-items:center;border-radius:9999px;background:#fef3c7;padding:.125rem .625rem;font-size:.7rem;font-weight:600;color:#92400e">
                                        Name + Phone
                                    </span>
                                @else
                                    <span style="display:inline-flex;align-items:center;border-radius:9999px;background:#fee2e2;padding:.125rem .625rem;font-size:.7rem;font-weight:600;color:#991b1b">
                                        Name only
                                    </span>
                                @endif
                            </td>
                            <td style="padding:.75rem 0">
                                <div style="display:flex;align-items:center;gap:.5rem">

                                    {{-- Yes: merge --}}
                                    <button
                                        type="button"
                                        wire:click="mergeOwner({{ $dup['id'] }})"
                                        wire:confirm="Merge '{{ addslashes($dup['name']) }}' into this owner? All their listings and phone numbers will be transferred and the duplicate will be removed."
                                        wire:loading.attr="disabled"
                                        style="display:inline-flex;align-items:center;gap:.375rem;border-radius:.5rem;background:#16a34a;padding:.375rem .75rem;font-size:.75rem;font-weight:600;color:#fff;border:none;cursor:pointer;transition:background .15s"
                                        onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='#16a34a'"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" width="14" height="14">
                                            <path fill-rule="evenodd" d="M12.416 3.376a.75.75 0 0 1 .208 1.04l-5 7.5a.75.75 0 0 1-1.154.114l-3-3a.75.75 0 0 1 1.06-1.06l2.353 2.353 4.493-6.74a.75.75 0 0 1 1.04-.207Z" clip-rule="evenodd" />
                                        </svg>
                                        Yes, Same Owner
                                    </button>

                                    {{-- No: dismiss --}}
                                    <button
                                        type="button"
                                        wire:click="dismissOwner({{ $dup['id'] }})"
                                        wire:loading.attr="disabled"
                                        style="display:inline-flex;align-items:center;gap:.375rem;border-radius:.5rem;background:#f3f4f6;padding:.375rem .75rem;font-size:.75rem;font-weight:600;color:#374151;border:none;cursor:pointer;transition:background .15s"
                                        onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" width="14" height="14">
                                            <path d="M5.28 4.22a.75.75 0 0 0-1.06 1.06L6.94 8l-2.72 2.72a.75.75 0 1 0 1.06 1.06L8 9.06l2.72 2.72a.75.75 0 1 0 1.06-1.06L9.06 8l2.72-2.72a.75.75 0 0 0-1.06-1.06L8 6.94 5.28 4.22Z" />
                                        </svg>
                                        No, Different Owner
                                    </button>

                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>
</div>
@endif
