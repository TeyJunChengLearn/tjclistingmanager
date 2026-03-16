@if (!empty($livewire->phoneConflicts))
<div style="margin-top:1rem">
    <div style="background:#fff;border-radius:.75rem;box-shadow:0 1px 3px 0 rgb(0 0 0/.1);border:1px solid #e5e7eb">

        {{-- Header --}}
        <div style="display:flex;align-items:flex-start;gap:.75rem;padding:1rem 1.5rem;border-bottom:1px solid #bfdbfe;background:#eff6ff;border-radius:.75rem .75rem 0 0">
            <div style="flex-shrink:0;margin-top:2px;color:#2563eb">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="20" height="20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM9 9a.75.75 0 0 0 0 1.5h.253a.25.25 0 0 1 .244.304l-.459 2.066A1.75 1.75 0 0 0 10.747 15H11a.75.75 0 0 0 0-1.5h-.253a.25.25 0 0 1-.244-.304l.459-2.066A1.75 1.75 0 0 0 9.253 9H9Z" clip-rule="evenodd" />
                </svg>
            </div>
            <div>
                <p style="margin:0;font-size:.875rem;font-weight:600;color:#1e40af">
                    Shared Phone Number{{ count($livewire->phoneConflicts) > 1 ? 's' : '' }} Detected
                </p>
                <p style="margin:.25rem 0 0;font-size:.75rem;color:#1d4ed8">
                    The following owner{{ count($livewire->phoneConflicts) > 1 ? 's are' : ' is' }}
                    registered under a different name but share{{ count($livewire->phoneConflicts) > 1 ? '' : 's' }}
                    the same phone number{{ count($livewire->phoneConflicts) > 1 ? 's' : '' }} with this owner.
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
                        <th style="padding-bottom:.625rem;padding-right:1rem;font-size:.75rem;font-weight:500;color:#6b7280">Shared Phone(s)</th>
                        <th style="padding-bottom:.625rem;font-size:.75rem;font-weight:500;color:#6b7280">Listings</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($livewire->phoneConflicts as $conflict)
                        <tr style="border-bottom:1px solid #f3f4f6;vertical-align:top">
                            <td style="padding:.75rem 1rem .75rem 0;font-weight:500;color:#111827">
                                {{ $conflict['name'] }}
                            </td>
                            <td style="padding:.75rem 1rem .75rem 0;color:#4b5563">
                                {{ $conflict['ic'] ?? '—' }}
                            </td>
                            <td style="padding:.75rem 1rem .75rem 0;color:#4b5563">
                                {{ implode(', ', $conflict['shared_numbers']) }}
                            </td>
                            <td style="padding:.75rem 0;color:#4b5563">
                                @if (!empty($conflict['listings']))
                                    @foreach ($conflict['listings'] as $listing)
                                        <div>{{ $listing['unit'] }} &mdash; {{ $listing['building'] }}</div>
                                    @endforeach
                                @else
                                    <span style="color:#9ca3af">No listings</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>
</div>
@endif
