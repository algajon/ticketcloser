@extends('layouts.saas')

@section('title', 'tickIt - Messaging')
@section('header_eyebrow', 'SMS follow-up')
@section('header', 'Messaging')
@section('header_description', 'Send short confirmation texts after calls create meetings or follow-up requests.')

@section('header_actions')
    @if($assistants->isEmpty())
        <a href="{{ $workspace->canCreateAssistants() ? route('app.assistant.create', $workspace) : route('app.billing.plans') }}" class="tc-btn-primary">
            {{ $workspace->canCreateAssistants() ? 'Create assistant' : 'Upgrade to create assistant' }}
        </a>
    @else
        <a href="{{ route('app.phone_numbers.index', $workspace) }}" class="tc-btn-primary">Assign number</a>
    @endif
@endsection

@section('content')
    @php
        $vapiConnected = filled(config('services.vapi.key'));
        $readyForMessaging = $smsReadyCount > 0 && $vapiConnected;
    @endphp

    <div class="space-y-6">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stat-card label="SMS-ready assistants" :value="$smsReadyCount" hint="Can text after booking" tone="emerald" />
            <x-ui.stat-card label="Vapi numbers" :value="$vapiNumberCount" hint="Text-capable lines" tone="blue" />
            <x-ui.stat-card label="Calendar providers" :value="$calendarProviderCount" hint="Booking destinations" tone="orange" />
            <x-ui.stat-card label="Pending bookings" :value="$pendingBookingCount" hint="Need confirmation" tone="amber" />
        </div>

        <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(340px,0.8fr)]">
            <div class="space-y-6">
                <x-ui.panel title="Messaging setup" description="Vapi-only messaging turns on when an assistant is synced and has an active Vapi-backed phone number.">
                    <div class="grid gap-3 md:grid-cols-3">
                        <div class="tc-meta-card {{ $assistants->isNotEmpty() ? 'border-emerald-200 bg-emerald-50/70' : 'border-amber-200 bg-amber-50/70' }}">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="tc-label-eyebrow-tight">Step 01</div>
                                    <div class="mt-2 text-sm font-semibold text-slate-950">Create assistant</div>
                                </div>
                                <x-ui.badge :tone="$assistants->isNotEmpty() ? 'success' : 'warning'">{{ $assistants->isNotEmpty() ? 'Done' : 'Needed' }}</x-ui.badge>
                            </div>
                            <p class="mt-3 text-sm leading-6 text-slate-600">The assistant needs the booking and SMS guardrails in its Vapi prompt.</p>
                        </div>

                        <div class="tc-meta-card {{ $vapiNumberCount > 0 ? 'border-emerald-200 bg-emerald-50/70' : 'border-amber-200 bg-amber-50/70' }}">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="tc-label-eyebrow-tight">Step 02</div>
                                    <div class="mt-2 text-sm font-semibold text-slate-950">Assign Vapi number</div>
                                </div>
                                <x-ui.badge :tone="$vapiNumberCount > 0 ? 'success' : 'warning'">{{ $vapiNumberCount > 0 ? 'Done' : 'Needed' }}</x-ui.badge>
                            </div>
                            <p class="mt-3 text-sm leading-6 text-slate-600">Texts are sent from the live Vapi number assigned to the assistant.</p>
                        </div>

                        <div class="tc-meta-card {{ $readyForMessaging ? 'border-emerald-200 bg-emerald-50/70' : 'border-slate-200 bg-slate-50/70' }}">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="tc-label-eyebrow-tight">Step 03</div>
                                    <div class="mt-2 text-sm font-semibold text-slate-950">Book follow-up</div>
                                </div>
                                <x-ui.badge :tone="$readyForMessaging ? 'success' : 'slate'">{{ $readyForMessaging ? 'On' : 'Waiting' }}</x-ui.badge>
                            </div>
                            <p class="mt-3 text-sm leading-6 text-slate-600">After `bookMeeting` succeeds, the assistant sends one short confirmation text.</p>
                        </div>
                    </div>

                    @if($phoneNumbersLockedForFreePlan)
                        <div class="mt-5 rounded-[1.25rem] border border-amber-200 bg-amber-50/80 p-4 text-sm leading-6 text-amber-900">
                            <div class="tc-label-eyebrow text-amber-700">Upgrade required</div>
                            <div class="mt-2">Free workspaces cannot assign live phone numbers, so SMS confirmations stay unavailable until the workspace is upgraded.</div>
                            <div class="mt-4">
                                <a href="{{ route('app.billing.plans') }}" class="tc-btn-primary !px-4 !py-2 text-sm">View plans</a>
                            </div>
                        </div>
                    @endif
                </x-ui.panel>

                <x-ui.panel title="Assistant messaging status" description="Each assistant follows the same path: create it, sync it to Vapi, then assign an active number.">
                    @if($assistantRows->isEmpty())
                        <x-ui.empty-state
                            title="No assistants yet"
                            description="Create an assistant first. Messaging will appear here as soon as that assistant is synced and connected to a number."
                            :actionText="$workspace->canCreateAssistants() ? 'Create assistant' : 'View plans'"
                            :actionHref="$workspace->canCreateAssistants() ? route('app.assistant.create', $workspace) : route('app.billing.plans')"
                        />
                    @else
                        <div class="space-y-3">
                            @foreach($assistantRows as $row)
                                @php
                                    $assistant = $row['assistant'];
                                    $phone = $row['phone'];
                                    $status = $row['status'];
                                    $phoneNumberHref = route('app.phone_numbers.index', ['workspace' => $workspace, 'assistant_id' => $assistant->id]);
                                @endphp

                                <div class="tc-meta-card-strong {{ $row['ready'] ? 'border-emerald-200 bg-emerald-50/60' : 'border-slate-200 bg-slate-50/70' }}">
                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <x-ui.badge :tone="$status['tone']">{{ $status['label'] }}</x-ui.badge>
                                                @if(filled($assistant->vapi_assistant_id))
                                                    <x-ui.badge tone="info">Synced to Vapi</x-ui.badge>
                                                @else
                                                    <x-ui.badge tone="warning">Needs sync</x-ui.badge>
                                                @endif
                                            </div>

                                            <h2 class="mt-3 truncate text-lg font-semibold text-slate-950">{{ $assistant->name }}</h2>

                                            <div class="mt-3 grid gap-3 text-sm md:grid-cols-2">
                                                <div>
                                                    <div class="tc-label-eyebrow-tight">From number</div>
                                                    <div class="mt-1 font-medium text-slate-900">
                                                        {{ $phone?->e164 ?: 'No number assigned' }}
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="tc-label-eyebrow-tight">Message behavior</div>
                                                    <div class="mt-1 text-slate-600">
                                                        {{ $row['ready'] ? 'Sends one confirmation after booking.' : 'Complete setup to enable confirmations.' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
                                            <a href="{{ route('app.assistant.show', [$workspace, $assistant]) }}" class="tc-btn-ghost w-full sm:w-auto">Edit assistant</a>
                                            <a href="{{ $phoneNumberHref }}" class="tc-btn-secondary w-full sm:w-auto">Manage number</a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-ui.panel>
            </div>

            <div class="space-y-6 xl:sticky xl:top-6 xl:self-start">
                <x-ui.panel title="What the caller receives" description="The assistant keeps texts short, transactional, and tied to the call.">
                    <div class="rounded-[1.35rem] border border-slate-200 bg-slate-950 p-5 text-slate-100">
                        <div class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-400">Example SMS</div>
                        <p class="mt-4 text-sm leading-7">
                            Thanks for calling {{ $workspace->name }}. Your follow-up is booked for Tue, 2:30 PM. Ticket TC-1042: kitchen leak. Reply to your team if anything changes.
                        </p>
                    </div>
                </x-ui.panel>

                <x-ui.panel title="Messaging guardrails" description="These rules are included in the assistant prompt and runtime overrides.">
                    <div class="space-y-3 text-sm leading-6 text-slate-700">
                        <div class="tc-meta-card-white">Send at most one SMS per call unless the caller asks for a correction.</div>
                        <div class="tc-meta-card-white">Keep the message under 320 characters.</div>
                        <div class="tc-meta-card-white">Include the scheduled date, time, ticket number when available, and a short issue label.</div>
                        <div class="tc-meta-card-white">Avoid sensitive medical, financial, legal, or highly private details.</div>
                    </div>
                </x-ui.panel>

                <x-ui.panel title="Provider path" description="Current Vapi-only implementation.">
                    <div class="space-y-3">
                        <div class="tc-meta-card {{ $vapiConnected ? 'border-emerald-200 bg-emerald-50/70' : 'border-amber-200 bg-amber-50/70' }}">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="text-sm font-semibold text-slate-950">Vapi API</div>
                                    <div class="mt-1 text-sm text-slate-600">{{ $vapiConnected ? 'Connected for assistant sync.' : 'Missing API key.' }}</div>
                                </div>
                                <x-ui.badge :tone="$vapiConnected ? 'success' : 'warning'">{{ $vapiConnected ? 'Ready' : 'Missing' }}</x-ui.badge>
                            </div>
                        </div>

                        <div class="tc-meta-card-white">
                            <div class="tc-label-eyebrow-tight">No separate SMS provider screen yet</div>
                            <p class="mt-2 text-sm leading-6 text-slate-600">For now, messaging uses Vapi's built-in SMS tool from the active Vapi phone number.</p>
                        </div>
                    </div>
                </x-ui.panel>
            </div>
        </div>
    </div>
@endsection
