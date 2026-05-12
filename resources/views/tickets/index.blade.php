@extends('layouts.saas')

@section('title', 'tickIt - Tickets')
@section('header_eyebrow', 'Ticket operations')
@section('header', 'Tickets')
@section('header_description', 'Search and review tickets for '.$workspace->name.'.')

@section('header_actions')
    <a href="{{ route('app.assistant.edit', $workspace) }}" class="tc-btn-secondary">Assistant settings</a>
@endsection

@section('content')
    @php
        $isPropertyManagement = $isPropertyManagement ?? false;
    @endphp

    <div class="space-y-6">
        <x-ui.panel title="Filters" description="Search by keyword, status, or assistant." bodyClass="p-0">
            <div class="tc-panel-body">
                <form method="GET" class="grid gap-4 xl:grid-cols-12 xl:items-end">
                    <div class="tc-field {{ $isPropertyManagement ? 'xl:col-span-5' : 'xl:col-span-8' }}">
                        <label for="search-q" class="tc-field-label">Search</label>
                        <input id="search-q" name="q" value="{{ $q }}" placeholder="Ticket number, title, phone, or email" class="tc-input" />
                    </div>

                    <div class="tc-field xl:col-span-2">
                        <label for="filter-status" class="tc-field-label">Status</label>
                        <select id="filter-status" name="status" class="tc-input">
                            <option value="" @selected($status === '')>All statuses</option>
                            <option value="new" @selected($status === 'new')>New</option>
                            <option value="triaged" @selected($status === 'triaged')>Triaged</option>
                            <option value="in_progress" @selected($status === 'in_progress')>In progress</option>
                            <option value="waiting" @selected($status === 'waiting')>Waiting</option>
                            <option value="resolved" @selected($status === 'resolved')>Resolved</option>
                            <option value="closed" @selected($status === 'closed')>Closed</option>
                        </select>
                    </div>

                    @if($isPropertyManagement)
                        <div class="tc-field xl:col-span-3">
                            <label for="filter-ops-stage" class="tc-field-label">Maintenance stage</label>
                            <select id="filter-ops-stage" name="ops_stage" class="tc-input">
                                <option value="" @selected($opsStage === '')>All stages</option>
                                @foreach($opsStageOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($opsStage === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="flex items-center gap-3 xl:col-span-2 xl:justify-end">
                        <button type="submit" class="tc-btn-primary whitespace-nowrap">Apply</button>
                        <a href="{{ route('app.tickets.index') }}" class="tc-btn-ghost !justify-center !px-2.5 whitespace-nowrap">Reset</a>
                    </div>

                    @if($isPropertyManagement)
                        <div class="xl:col-span-12">
                            <div class="rounded-[1.2rem] border border-slate-200 bg-slate-50/80 px-4 py-4">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                    <div>
                                        <div class="text-[0.7rem] font-semibold uppercase tracking-[0.22em] text-slate-500">Queue views</div>
                                        <p class="mt-1 text-sm text-slate-600">Jump straight to the maintenance work that usually needs action first.</p>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                                        <a href="{{ request()->fullUrlWithQuery(['ops_stage' => 'urgent_review', 'status' => '']) }}" class="tc-chip {{ $opsStage === 'urgent_review' ? 'tc-chip-active' : '' }}">Urgent review</a>
                                        <a href="{{ request()->fullUrlWithQuery(['ops_stage' => 'dispatched', 'status' => '']) }}" class="tc-chip {{ $opsStage === 'dispatched' ? 'tc-chip-active' : '' }}">Dispatched</a>
                                        <a href="{{ request()->fullUrlWithQuery(['ops_stage' => 'scheduled', 'status' => '']) }}" class="tc-chip {{ $opsStage === 'scheduled' ? 'tc-chip-active' : '' }}">Scheduled</a>
                                        <a href="{{ request()->fullUrlWithQuery(['ops_stage' => 'waiting_on_resident', 'status' => '']) }}" class="tc-chip {{ $opsStage === 'waiting_on_resident' ? 'tc-chip-active' : '' }}">Waiting on resident</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($assistants->count() > 0)
                        <div class="xl:col-span-12">
                            <div class="mt-2 border-t border-slate-200/80 pt-4">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                    <div>
                                        <div class="text-[0.7rem] font-semibold uppercase tracking-[0.22em] text-slate-500">Assistant</div>
                                        <p class="mt-1 text-sm text-slate-600">Show tickets from one assistant.</p>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                                        <a href="{{ request()->fullUrlWithQuery(['assistant' => '']) }}" class="tc-chip {{ $assistant === '' ? 'tc-chip-active' : '' }}">
                                            All
                                            <span class="text-xs opacity-75">({{ $totalCases }})</span>
                                        </a>
                                        @foreach($assistants as $asst)
                                            <a href="{{ request()->fullUrlWithQuery(['assistant' => $asst->id]) }}" class="tc-chip {{ (string) $assistant === (string) $asst->id ? 'tc-chip-active' : '' }}">
                                                {{ $asst->name }}
                                                <span class="text-xs opacity-75">({{ $assistantCaseCounts[$asst->id] ?? 0 }})</span>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </form>
            </div>
        </x-ui.panel>

        <x-ui.panel :title="$cases->total().' '.\Illuminate\Support\Str::plural('ticket', $cases->total())" description="Newest first.">
            @if($cases->count() === 0)
                <x-ui.empty-state title="No tickets found" description="Tickets will show up here after calls are handled." actionText="Go to dashboard" :actionHref="route('app.dashboard')" />
            @else
                <div class="space-y-3">
                    @foreach($cases as $case)
                        @php
                            $statusTone = \App\Models\SupportCase::statusTone($case->status);
                            $priorityTone = \App\Models\SupportCase::priorityTone($case->priority);
                            $opsStageTone = \App\Models\SupportCase::opsStageTone($case->ops_stage);
                        @endphp
                        <a href="{{ route('app.tickets.show', $case->id) }}" class="block rounded-[1.45rem] border border-slate-200 bg-slate-50/70 px-5 py-5 transition hover:border-slate-300 hover:bg-white hover:shadow-[0_22px_48px_-34px_rgba(15,23,42,0.34)]">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ $case->case_number }}</span>
                                        <x-ui.badge :tone="$statusTone">{{ str_replace('_', ' ', $case->status) }}</x-ui.badge>
                                        <x-ui.badge :tone="$priorityTone">{{ $case->priority }}</x-ui.badge>
                                        @if($isPropertyManagement && $case->ops_stage)
                                            <x-ui.badge :tone="$opsStageTone">{{ \App\Models\SupportCase::opsStageLabel($case->ops_stage) }}</x-ui.badge>
                                        @endif
                                    </div>

                                    <p class="mt-3 text-base font-semibold text-slate-950">{{ $case->title }}</p>
                                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">{{ \Illuminate\Support\Str::limit($case->description, 190) }}</p>

                                    <div class="mt-4 flex flex-wrap items-center gap-4 text-xs uppercase tracking-[0.14em] text-slate-500">
                                        @if($case->category)
                                            <span>{{ $case->category }}</span>
                                        @endif
                                        @if($case->source)
                                            <span>{{ $case->source }}</span>
                                        @endif
                                        @if($case->requester_phone || $case->requester_email)
                                            <span>{{ $case->requester_phone ?? $case->requester_email }}</span>
                                        @endif
                                    </div>

                                    @if($isPropertyManagement)
                                        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                                            <div class="rounded-[1rem] border border-slate-200 bg-white/80 px-4 py-3">
                                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-slate-500">Resident</div>
                                                <div class="mt-2 text-sm font-semibold text-slate-900">{{ $case->residentNameDisplay() ?: 'Caller on file' }}</div>
                                                <p class="mt-1 text-sm leading-6 text-slate-600">{{ $case->requester_phone ?: 'No callback number saved' }}</p>
                                            </div>
                                            <div class="rounded-[1rem] border border-slate-200 bg-white/80 px-4 py-3">
                                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-slate-500">Property</div>
                                                <div class="mt-2 text-sm font-semibold text-slate-900">{{ $case->propertyDisplay() ?: 'Property not saved yet' }}</div>
                                                <p class="mt-1 text-sm leading-6 text-slate-600">{{ $case->unitDisplay() ? 'Unit '.$case->unitDisplay() : 'Unit not saved yet' }}</p>
                                            </div>
                                            <div class="rounded-[1rem] border border-slate-200 bg-white/80 px-4 py-3">
                                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-slate-500">Access</div>
                                                <p class="mt-2 text-sm leading-6 text-slate-700">{{ \Illuminate\Support\Str::limit($case->accessDetailsDisplay() ?: 'No access notes saved yet.', 90) }}</p>
                                            </div>
                                            <div class="rounded-[1rem] border border-slate-200 bg-white/80 px-4 py-3">
                                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-slate-500">Visit / vendor</div>
                                                <div class="mt-2 text-sm font-semibold text-slate-900">{{ $case->preferredVisitWindowDisplay() ?: 'No visit window yet' }}</div>
                                                <p class="mt-1 text-sm leading-6 text-slate-600">{{ $case->vendor_name ? $case->vendor_name.($case->vendor_phone ? ' • '.$case->vendor_phone : '') : 'No vendor assigned yet' }}</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div class="shrink-0 text-right text-sm text-slate-500">
                                    <div>{{ $case->created_at->format('M j, Y') }}</div>
                                    <div class="mt-1 text-xs uppercase tracking-[0.16em]">{{ $case->created_at->format('g:i A') }}</div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                @if($cases->hasPages())
                    <x-slot:footer>
                        <div class="w-full">
                            {{ $cases->withQueryString()->links() }}
                        </div>
                    </x-slot:footer>
                @endif
            @endif
        </x-ui.panel>
    </div>
@endsection
