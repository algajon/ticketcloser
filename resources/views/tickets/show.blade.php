@extends('layouts.saas')

@section('title', 'tickIt - Ticket '.$case->case_number)
@section('header_eyebrow', 'Ticket detail')
@section('header', 'Ticket '.$case->case_number)
@section('header_description', $isPropertyManagement ? 'Resident context, maintenance workflow, and follow-up details for this ticket.' : 'Status, caller details, transcript context, and meetings for this ticket.')

@section('header_actions')
    <a href="{{ route('app.tickets.index') }}" class="tc-btn-secondary">Back to tickets</a>
@endsection

@section('header_meta')
    @php
        $statusTone = \App\Models\SupportCase::statusTone($case->status);
        $priorityTone = \App\Models\SupportCase::priorityTone($case->priority);
    @endphp

    <div class="flex flex-wrap items-center gap-2">
        <x-ui.badge :tone="$statusTone">{{ str_replace('_', ' ', $case->status) }}</x-ui.badge>
        <x-ui.badge :tone="$priorityTone">{{ $case->priority }} priority</x-ui.badge>
        @if($isPropertyManagement && $case->ops_stage)
            <x-ui.badge :tone="\App\Models\SupportCase::opsStageTone($case->ops_stage)">{{ \App\Models\SupportCase::opsStageLabel($case->ops_stage) }}</x-ui.badge>
        @endif
        @if($case->category)
            <x-ui.badge tone="slate">{{ $case->category }}</x-ui.badge>
        @endif
        <x-ui.badge tone="slate">{{ $case->source ?? 'voice' }}</x-ui.badge>
    </div>
@endsection

@section('content')
    @php
        $statusTone = \App\Models\SupportCase::statusTone($case->status);
        $priorityTone = \App\Models\SupportCase::priorityTone($case->priority);
    @endphp

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.25fr)_minmax(320px,0.85fr)]">
        <div class="space-y-6">
            @if($isPropertyManagement)
                <x-ui.panel title="Maintenance snapshot" description="The details your team usually needs right away.">
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50/80 p-4">
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Resident</div>
                            <div class="mt-2 text-base font-semibold text-slate-950">{{ $case->residentNameDisplay() ?: 'Caller on file' }}</div>
                            <p class="mt-2 break-words text-sm leading-6 text-slate-600">{{ $case->requester_phone ?: 'No callback number saved yet.' }}</p>
                        </div>
                        <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50/80 p-4">
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Property</div>
                            <div class="mt-2 text-base font-semibold text-slate-950">{{ $case->propertyDisplay() ?: 'Property not saved yet' }}</div>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $case->unitDisplay() ? 'Unit '.$case->unitDisplay() : 'Unit not saved yet' }}</p>
                        </div>
                        <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50/80 p-4">
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Access</div>
                            <p class="mt-2 text-sm leading-6 text-slate-700">{{ $case->accessDetailsDisplay() ?: 'No access notes saved yet.' }}</p>
                        </div>
                        <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50/80 p-4">
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Visit window</div>
                            <div class="mt-2 text-base font-semibold text-slate-950">{{ $case->preferredVisitWindowDisplay() ?: 'Not scheduled yet' }}</div>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $case->vendor_name ? $case->vendor_name.($case->vendor_phone ? ' • '.$case->vendor_phone : '') : 'No vendor assigned yet' }}</p>
                        </div>
                    </div>
                </x-ui.panel>
            @endif

            <x-ui.panel title="{{ $case->title }}" description="Created {{ $case->created_at->format('M j, Y \a\t g:i A') }}">
                <div class="grid gap-6 lg:grid-cols-[minmax(0,1.15fr)_minmax(240px,0.85fr)]">
                    <div>
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Description</div>
                        <p class="mt-3 whitespace-pre-wrap break-words text-sm leading-7 text-slate-700">{{ $case->description }}</p>
                    </div>

                    <div class="rounded-[1.35rem] border border-slate-200 bg-slate-50/80 p-4">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Ticket summary</div>
                        <div class="mt-4 space-y-3 text-sm text-slate-600">
                            <div class="flex flex-col gap-1.5 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                                <span>Status</span>
                                <x-ui.badge :tone="$statusTone">{{ str_replace('_', ' ', $case->status) }}</x-ui.badge>
                            </div>
                            <div class="flex flex-col gap-1.5 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                                <span>Priority</span>
                                <x-ui.badge :tone="$priorityTone">{{ $case->priority }}</x-ui.badge>
                            </div>
                            <div class="flex flex-col gap-1.5 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                                <span>Source</span>
                                <span class="font-medium text-slate-900">{{ $case->source ?? 'voice' }}</span>
                            </div>
                            @if($case->category)
                                <div class="flex flex-col gap-1.5 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                                    <span>Category</span>
                                    <span class="font-medium text-slate-900">{{ $case->category }}</span>
                                </div>
                            @endif
                            @if($isPropertyManagement && $case->ops_stage)
                                <div class="flex flex-col gap-1.5 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                                    <span>Maintenance stage</span>
                                    <x-ui.badge :tone="\App\Models\SupportCase::opsStageTone($case->ops_stage)">{{ \App\Models\SupportCase::opsStageLabel($case->ops_stage) }}</x-ui.badge>
                                </div>
                            @endif
                            @if($case->transcriptLanguageLabel())
                                <div class="flex flex-col gap-1.5 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                                    <span>Call language</span>
                                    <span class="font-medium text-slate-900">{{ $case->transcriptLanguageLabel() }}</span>
                                </div>
                            @endif
                            @if($case->transcriberLabel())
                                <div class="flex flex-col gap-1.5 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                                    <span>Speech stack</span>
                                    <span class="font-medium text-slate-900">{{ $case->transcriberLabel() }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </x-ui.panel>

            <x-ui.panel title="Activity" description="Everything that has happened on this ticket.">
                @forelse($case->events as $event)
                    <div class="relative border-l border-slate-200 pl-6 {{ !$loop->last ? 'pb-6' : '' }}">
                        <span class="tc-accent-fill absolute -left-[0.42rem] top-1.5 h-3 w-3 rounded-full border-2 border-white"></span>
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div class="text-sm font-semibold text-slate-950">{{ $event->type }}</div>
                            <div class="text-xs uppercase tracking-[0.16em] text-slate-500">{{ $event->created_at->format('M j, g:i A') }}</div>
                        </div>
                        @if($event->data)
                            <pre class="mt-3 overflow-auto rounded-[1.2rem] border border-slate-200 bg-slate-50 px-4 py-4 text-xs leading-6 text-slate-700">{{ json_encode($event->data, JSON_PRETTY_PRINT) }}</pre>
                        @else
                            <p class="mt-3 text-sm leading-6 text-slate-600">No additional event payload is available for this step.</p>
                        @endif
                    </div>
                @empty
                    <x-ui.empty-state title="No activity yet" description="Updates will show up here as this ticket moves forward." />
                @endforelse
            </x-ui.panel>
        </div>

        <div class="space-y-6">
            @if($isPropertyManagement)
                <x-ui.panel title="Maintenance workflow" description="Move the ticket forward without losing the resident context.">
                    <form method="POST" action="{{ route('app.cases.workflow.update', [$workspace, $case]) }}" class="space-y-4">
                        @csrf
                        <div class="tc-field">
                            <label for="ops-stage-select" class="tc-field-label">Stage</label>
                            <select id="ops-stage-select" name="ops_stage" class="tc-input">
                                <option value="">No maintenance stage yet</option>
                                @foreach($opsStageOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($case->ops_stage === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="tc-field">
                                <label for="vendor-name" class="tc-field-label">Vendor or technician</label>
                                <input id="vendor-name" type="text" name="vendor_name" value="{{ old('vendor_name', $case->vendor_name) }}" class="tc-input" placeholder="Acme Plumbing" />
                            </div>
                            <div class="tc-field">
                                <label for="vendor-phone" class="tc-field-label">Vendor phone</label>
                                <input id="vendor-phone" type="text" name="vendor_phone" value="{{ old('vendor_phone', $case->vendor_phone) }}" class="tc-input" placeholder="+1 555 123 4567" />
                            </div>
                        </div>

                        <div class="tc-field">
                            <label for="preferred-visit-window" class="tc-field-label">Preferred visit window</label>
                            <input id="preferred-visit-window" type="text" name="preferred_visit_window" value="{{ old('preferred_visit_window', $case->preferred_visit_window ?: $case->preferredVisitWindowDisplay()) }}" class="tc-input" placeholder="Tomorrow after 3 PM" />
                        </div>

                        <div class="tc-field">
                            <label for="access-notes" class="tc-field-label">Access notes</label>
                            <textarea id="access-notes" name="access_notes" rows="4" class="tc-textarea" placeholder="Keys are in the lockbox, ring unit 6 first, resident wants notice before entry.">{{ old('access_notes', $case->access_notes ?: $case->accessDetailsDisplay()) }}</textarea>
                        </div>

                        <button type="submit" class="tc-btn-primary w-full justify-center">Save maintenance workflow</button>
                    </form>
                </x-ui.panel>
            @endif

            @if($isPropertyManagement && $vendorSuggestions->isNotEmpty())
                <x-ui.panel title="Suggested vendors" description="Based on similar tickets and past assignments in this workspace.">
                    <div class="space-y-3">
                        @foreach($vendorSuggestions as $suggestion)
                            <div class="rounded-[1.2rem] border border-slate-200 bg-slate-50/80 p-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="min-w-0">
                                        <div class="text-sm font-semibold text-slate-950">{{ $suggestion['vendor_name'] }}</div>
                                        <div class="mt-1 text-sm leading-6 text-slate-600">{{ $suggestion['vendor_phone'] ?: 'No phone saved' }}</div>
                                        <div class="mt-3 flex flex-wrap gap-2 text-xs uppercase tracking-[0.14em] text-slate-500">
                                            <span>{{ $suggestion['ticket_count'] }} prior {{ $suggestion['ticket_count'] === 1 ? 'ticket' : 'tickets' }}</span>
                                            @if($suggestion['same_category_count'] > 0)
                                                <span>{{ $suggestion['same_category_count'] }} in this category</span>
                                            @endif
                                            @if($suggestion['same_property_count'] > 0)
                                                <span>{{ $suggestion['same_property_count'] }} at this property</span>
                                            @endif
                                        </div>
                                        @if($suggestion['latest_case'])
                                            <p class="mt-3 text-sm leading-6 text-slate-600">
                                                Last used on {{ $suggestion['latest_case']->case_number }} for {{ $suggestion['latest_case']->title }}.
                                            </p>
                                        @endif
                                    </div>

                                    <form method="POST" action="{{ route('app.cases.workflow.update', [$workspace, $case]) }}" class="shrink-0">
                                        @csrf
                                        <input type="hidden" name="ops_stage" value="{{ old('ops_stage', $case->ops_stage ?: \App\Models\SupportCase::OPS_STAGE_DISPATCHED) }}">
                                        <input type="hidden" name="vendor_name" value="{{ $suggestion['vendor_name'] }}">
                                        <input type="hidden" name="vendor_phone" value="{{ $suggestion['vendor_phone'] }}">
                                        <input type="hidden" name="preferred_visit_window" value="{{ old('preferred_visit_window', $case->preferred_visit_window ?: $case->preferredVisitWindowDisplay()) }}">
                                        <input type="hidden" name="access_notes" value="{{ old('access_notes', $case->access_notes ?: $case->accessDetailsDisplay()) }}">
                                        <button type="submit" class="tc-btn-secondary">Use vendor</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-ui.panel>
            @endif

            <x-ui.panel title="Update status" description="Keep the queue accurate.">
                <form method="POST" action="{{ route('app.cases.status.update', [$workspace, $case]) }}" class="space-y-4" x-data="{ loading: false }" @submit="loading = true">
                    @csrf
                    <div class="tc-field">
                        <label for="status-select" class="tc-field-label">Status</label>
                        <select id="status-select" name="status" class="tc-input">
                            @foreach(['new', 'triaged', 'in_progress', 'waiting', 'resolved', 'closed'] as $s)
                                <option value="{{ $s }}" @selected($case->status === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="tc-btn-primary w-full justify-center" x-bind:disabled="loading">
                        <span x-text="loading ? 'Saving...' : 'Update status'">Update status</span>
                    </button>
                </form>
            </x-ui.panel>

            <x-ui.panel title="Meetings" description="Bookings tied to this ticket.">
                <div class="space-y-3">
                    @forelse($case->calendarEvents as $event)
                        <div class="rounded-[1.25rem] border border-emerald-200 bg-emerald-50/70 p-4">
                            <div class="text-sm font-semibold text-emerald-900">Confirmed meeting</div>
                            <div class="mt-2 text-xs uppercase tracking-[0.16em] text-emerald-700">{{ \Carbon\Carbon::parse($event->starts_at)->format('M j, Y \a\t g:i A') }}</div>
                            @if($event->url)
                                <a href="{{ $event->url }}" target="_blank" class="mt-3 inline-flex text-sm font-medium text-emerald-800 transition hover:text-emerald-950">Open join link</a>
                            @endif
                        </div>
                    @empty
                    @endforelse

                    @forelse($case->suggestedEvents->where('status', 'pending') as $suggested)
                        <div class="rounded-[1.25rem] border border-amber-200 bg-amber-50/70 p-4">
                            <div class="text-sm font-semibold text-amber-900">Suggested meeting</div>
                            <div class="mt-2 text-xs uppercase tracking-[0.16em] text-amber-700">{{ \Carbon\Carbon::parse($suggested->starts_at)->format('M j, Y \a\t g:i A') }}</div>
                            <a href="{{ route('app.calendar.index') }}" class="mt-3 inline-flex text-sm font-medium text-amber-800 transition hover:text-amber-950">Review in calendar</a>
                        </div>
                    @empty
                    @endforelse

                    @if($case->calendarEvents->isEmpty() && $case->suggestedEvents->where('status', 'pending')->isEmpty())
                        <x-ui.empty-state title="No meetings yet" description="Meetings will show up here if this ticket needs follow-up." />
                    @endif
                </div>
            </x-ui.panel>

            <x-ui.panel title="Caller" description="The contact linked to this ticket.">
                <div class="space-y-4">
                    @if($case->contact)
                        <div class="rounded-[1.2rem] border border-slate-200 bg-slate-50/80 p-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Linked contact</div>
                                    <div class="mt-2 text-base font-semibold text-slate-950">{{ $case->contact->name ?: 'Unnamed contact' }}</div>
                                    @if($case->contact->property_code || $case->contact->unit)
                                        <div class="mt-1 text-sm text-slate-600">
                                            {{ $case->contact->property_code ?: 'Property on file' }}@if($case->contact->unit), Unit {{ $case->contact->unit }}@endif
                                        </div>
                                    @endif
                                </div>
                                <a href="{{ route('app.contacts.show', [$workspace, $case->contact]) }}" class="tc-btn-secondary shrink-0">
                                    View contact
                                </a>
                            </div>
                        </div>
                    @endif
                    <div class="rounded-[1.2rem] border border-slate-200 bg-slate-50/80 p-4">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Phone</div>
                        <div class="mt-2 break-all text-sm font-medium text-slate-900">{{ $case->contact?->phone_e164 ?? $case->requester_phone ?? 'Not provided' }}</div>
                    </div>
                    <div class="rounded-[1.2rem] border border-slate-200 bg-slate-50/80 p-4">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Email</div>
                        <div class="mt-2 break-all text-sm font-medium text-slate-900">{{ $case->contact?->email ?? $case->requester_email ?? 'Not provided' }}</div>
                    </div>
                </div>
            </x-ui.panel>

            @if($isPropertyManagement && $relatedResidentCases->isNotEmpty())
                <x-ui.panel title="Resident history" description="Recent tickets tied to this caller.">
                    <div class="space-y-3">
                        @foreach($relatedResidentCases as $relatedCase)
                            <a href="{{ route('app.tickets.show', $relatedCase->id) }}" class="block rounded-[1.15rem] border border-slate-200 bg-slate-50/80 px-4 py-4 transition hover:border-slate-300 hover:bg-white">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">{{ $relatedCase->case_number }}</div>
                                        <div class="mt-2 text-sm font-semibold text-slate-950">{{ $relatedCase->title }}</div>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <x-ui.badge :tone="\App\Models\SupportCase::statusTone($relatedCase->status)">{{ str_replace('_', ' ', $relatedCase->status) }}</x-ui.badge>
                                        <x-ui.badge :tone="\App\Models\SupportCase::priorityTone($relatedCase->priority)">{{ $relatedCase->priority }}</x-ui.badge>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </x-ui.panel>
            @endif

            <x-ui.panel title="Reference IDs" description="Call IDs and external references saved with this ticket.">
                @if($case->external_call_id)
                    <div x-data="{ copied: false }" class="rounded-[1.2rem] border border-slate-200 bg-slate-50/80 p-4">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">External call ID</div>
                        <div class="mt-3 flex items-center gap-3">
                            <code class="min-w-0 flex-1 overflow-hidden text-ellipsis break-all text-xs text-slate-700">{{ $case->external_call_id }}</code>
                            <button type="button" class="tc-btn-ghost shrink-0 !px-3 !py-2 text-xs" @click="navigator.clipboard.writeText('{{ $case->external_call_id }}').then(() => { copied = true; setTimeout(() => copied = false, 1800) })">
                                <span x-show="!copied">Copy</span>
                                <span x-show="copied" x-cloak>Copied</span>
                            </button>
                        </div>
                    </div>
                @else
                    <x-ui.empty-state title="No reference IDs" description="This ticket does not include an external call ID yet." />
                @endif
            </x-ui.panel>
        </div>
    </div>
@endsection
