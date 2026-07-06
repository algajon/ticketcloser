@extends('layouts.saas')

@section('title', 'tickIt - Messaging')
@section('header_eyebrow', 'SMS follow-up')
@section('header', 'Messaging')

@section('header_actions')
    <a href="{{ route('app.phone_numbers.index', $workspace) }}" class="tc-btn-secondary">Phone numbers</a>
    <a href="{{ route('app.calendar.index') }}" class="tc-btn-primary">Review bookings</a>
@endsection

@section('content')
    @php
        $vapiConnected = filled(config('services.vapi.key'));
        $readyForMessaging = $smsReadyCount > 0 && $vapiConnected && $settings->booking_confirmation_enabled;
        $templateValue = old('booking_confirmation_template', $settings->booking_confirmation_template);
        $signatureValue = old('signature', $settings->signature);
        $brandVoiceValue = old('brand_voice', $settings->brand_voice);
        $bookingEnabledValue = old('booking_confirmation_enabled', $settings->booking_confirmation_enabled) ? true : false;
        $includeTicketValue = old('include_ticket_number', $settings->include_ticket_number) ? true : false;
        $includeIssueValue = old('include_issue_label', $settings->include_issue_label) ? true : false;
        $replyCaptureValue = old('reply_capture_enabled', $settings->reply_capture_enabled) ? true : false;
        $replyMessages = $recentMessages
            ->filter(fn ($message) => $message->direction === \App\Models\MessageEvent::DIRECTION_INBOUND || filled($message->response_body))
            ->take(4);
        $messageTokens = [
            ['key' => 'customer_name', 'label' => 'Caller name', 'sample' => 'Maria'],
            ['key' => 'workspace_name', 'label' => 'Business name', 'sample' => $workspace->name],
            ['key' => 'appointment_time', 'label' => 'Appointment time', 'sample' => 'Tue, Jul 7 at 2:30 PM'],
            ['key' => 'ticket_number', 'label' => 'Ticket number', 'sample' => 'TC-1042'],
            ['key' => 'issue_label', 'label' => 'Issue summary', 'sample' => 'Kitchen leak'],
            ['key' => 'signature', 'label' => 'Signature', 'sample' => $signatureValue],
        ];
        $friendlyTemplateValue = collect($messageTokens)->reduce(
            fn (string $template, array $token) => str_replace('{{'.$token['key'].'}}', '['.$token['label'].']', $template),
            $templateValue,
        );
    @endphp

    <div
        class="space-y-7"
        x-data="{
            messageTokens: @js($messageTokens),
            templateDisplay: @js($friendlyTemplateValue),
            signature: @js($signatureValue),
            bookingEnabled: @js($bookingEnabledValue),
            includeTicket: @js($includeTicketValue),
            includeIssue: @js($includeIssueValue),
            replyCapture: @js($replyCaptureValue),
            rawTemplate() {
                let output = this.templateDisplay || '';
                const open = String.fromCharCode(123, 123);
                const close = String.fromCharCode(125, 125);

                this.messageTokens.forEach((token) => {
                    output = output.replaceAll('[' + token.label + ']', open + token.key + close);
                });

                return output;
            },
            insertToken(key) {
                const token = this.messageTokens.find((item) => item.key === key);

                if (! token) {
                    return;
                }

                const textarea = this.$refs.templateInput;
                const insert = '[' + token.label + ']';

                if (! textarea) {
                    this.templateDisplay = (this.templateDisplay ? this.templateDisplay + ' ' : '') + insert;
                    return;
                }

                const start = textarea.selectionStart ?? this.templateDisplay.length;
                const end = textarea.selectionEnd ?? this.templateDisplay.length;
                const before = this.templateDisplay.slice(0, start);
                const after = this.templateDisplay.slice(end);
                const prefix = before && ! before.endsWith(' ') && ! before.endsWith('\n') ? ' ' : '';
                const suffix = after && ! after.startsWith(' ') && ! after.startsWith('\n') && ! after.startsWith('.') ? ' ' : '';

                this.templateDisplay = before + prefix + insert + suffix + after;

                this.$nextTick(() => {
                    const cursor = (before + prefix + insert).length;
                    textarea.focus();
                    textarea.setSelectionRange(cursor, cursor);
                });
            },
            renderPreview() {
                let output = this.rawTemplate();
                const open = String.fromCharCode(123, 123);
                const close = String.fromCharCode(125, 125);
                const values = {
                    customer_name: 'Maria',
                    workspace_name: @js($workspace->name),
                    appointment_time: 'Tue, Jul 7 at 2:30 PM',
                    ticket_number: this.includeTicket ? 'Ticket TC-1042.' : '',
                    issue_label: this.includeIssue ? 'Kitchen leak.' : '',
                    signature: this.signature || '',
                };

                Object.entries(values).forEach(([key, value]) => {
                    output = output.replaceAll(open + key + close, String(value || '').trim());
                });

                return output.replace(/\s+/g, ' ').trim();
            },
            remaining() {
                return Math.max(0, 320 - this.renderPreview().length);
            },
        }"
    >
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stat-card label="Sent messages" :value="$sentCount" hint="Outbound confirmations" tone="blue" />
            <x-ui.stat-card label="Delivery rate" :value="$deliveryRate.'%'" :hint="$deliveredCount.' delivered'" tone="emerald" />
            <x-ui.stat-card label="Open / read proxy" :value="$openProxyRate.'%'" hint="Clicks, replies, or provider reads" tone="orange" />
            <x-ui.stat-card label="Reply rate" :value="$replyRate.'%'" :hint="$replyCount.' replies captured'" tone="amber" />
        </div>

        <form method="POST" action="{{ route('app.messaging.update', $workspace) }}" class="space-y-7">
            @csrf
            <input type="hidden" name="booking_confirmation_template" :value="rawTemplate()">

            <div class="grid items-stretch gap-7 xl:grid-cols-[minmax(0,1.05fr)_minmax(380px,0.95fr)]">
                <section class="tc-panel overflow-hidden">
                    <div class="border-b border-slate-200 px-6 py-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <div class="tc-label-eyebrow">Customize messages</div>
                                <h2 class="tc-h3 mt-1">Booking confirmation</h2>
                            </div>
                            <label class="tc-accent-surface inline-flex cursor-pointer items-center gap-3 rounded-full border px-4 py-2">
                                <input type="checkbox" class="tc-accent-control h-4 w-4 rounded border-slate-300" x-model="bookingEnabled">
                                <span class="text-sm font-semibold tc-accent-text-strong" x-text="bookingEnabled ? 'Auto-send on' : 'Auto-send off'"></span>
                            </label>
                        </div>
                    </div>

                    <div class="space-y-6 p-6">
                        <div class="tc-field">
                            <label for="booking_confirmation_template_display" class="tc-field-label">Message callers receive</label>
                            <textarea
                                id="booking_confirmation_template_display"
                                x-ref="templateInput"
                                rows="7"
                                class="tc-textarea text-base leading-7"
                                x-model="templateDisplay"
                                placeholder="Hi [Caller name], your follow-up is booked for [Appointment time]."
                            ></textarea>
                            @error('booking_confirmation_template')
                                <p class="tc-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="rounded-[1.45rem] border border-slate-200 bg-slate-50/75 p-4">
                            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <div class="text-sm font-semibold text-slate-950">Add saved details</div>
                                    <p class="mt-1 text-sm leading-6 text-slate-600">Pick the plain-English labels. Callers only see the finished text.</p>
                                </div>
                                <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-500 shadow-sm">No code shown</span>
                            </div>
                            <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                                @foreach($messageTokens as $token)
                                    <button
                                        type="button"
                                        class="group rounded-[1.1rem] border border-slate-200 bg-white px-4 py-3 text-left transition hover:-translate-y-0.5 hover:border-[rgb(var(--tc-primary)/0.45)] hover:bg-[rgb(var(--tc-primary)/0.05)]"
                                        @click="insertToken(@js($token['key']))"
                                    >
                                        <span class="block text-sm font-semibold text-slate-950">{{ $token['label'] }}</span>
                                        <span class="mt-1 block truncate text-xs text-slate-500">Example: {{ $token['sample'] ?: 'saved value' }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="tc-field">
                                <label for="signature" class="tc-field-label">Signature</label>
                                <input id="signature" name="signature" type="text" class="tc-input" x-model="signature" value="{{ $signatureValue }}" placeholder="- {{ $workspace->name }}">
                                @error('signature')
                                    <p class="tc-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="tc-field">
                                <label for="brand_voice" class="tc-field-label">Message tone</label>
                                <select id="brand_voice" name="brand_voice" class="tc-input">
                                    @foreach($brandVoiceOptions as $value => $label)
                                        <option value="{{ $value }}" @selected($brandVoiceValue === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('brand_voice')
                                    <p class="tc-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </section>

                <section class="tc-panel overflow-hidden">
                    <div class="border-b border-slate-200 px-6 py-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="tc-label-eyebrow">Chat preview</div>
                                <h2 class="tc-h3 mt-1">Caller phone</h2>
                            </div>
                            <x-ui.badge :tone="$readyForMessaging ? 'success' : 'warning'">{{ $readyForMessaging ? 'Live' : 'Setup needed' }}</x-ui.badge>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="rounded-[2rem] border border-slate-800 bg-slate-950 p-5 text-white shadow-[0_26px_70px_-36px_rgba(15,23,42,0.7)]">
                            <div class="flex items-center justify-between">
                                <div class="flex gap-2">
                                    <span class="h-3 w-3 rounded-full bg-red-400"></span>
                                    <span class="h-3 w-3 rounded-full bg-amber-400"></span>
                                    <span class="h-3 w-3 rounded-full bg-emerald-400"></span>
                                </div>
                                <span class="rounded-full border border-white/10 bg-white/10 px-3 py-1 text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-slate-300">SMS</span>
                            </div>

                            <div class="mt-8 space-y-5">
                                <div class="max-w-[82%] rounded-3xl rounded-bl-md bg-white/10 px-5 py-4 text-sm leading-6 text-slate-200">
                                    Thanks, Tuesday at 2:30 works.
                                </div>
                                <div class="ml-auto max-w-[92%] rounded-3xl rounded-br-md bg-[rgb(var(--tc-primary))] px-5 py-4 text-sm font-semibold leading-6 text-white shadow-[0_18px_45px_-26px_rgb(var(--tc-primary)/0.9)]">
                                    <span x-text="renderPreview()">{{ $messagePreview }}</span>
                                </div>
                            </div>

                            <div class="mt-8 grid gap-3 sm:grid-cols-2">
                                <div class="rounded-[1.2rem] bg-white/7 p-4">
                                    <div class="text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-slate-500">Length</div>
                                    <div class="mt-1 text-sm font-semibold text-slate-200" x-text="remaining() + ' chars left'"></div>
                                </div>
                                <div class="rounded-[1.2rem] bg-white/7 p-4">
                                    <div class="text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-slate-500">Template</div>
                                    <div class="mt-1 text-sm font-semibold text-slate-200">{{ $settings->updated_at ? 'Updated '.$settings->updated_at->diffForHumans() : 'Default' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <section class="tc-panel overflow-hidden">
                <div class="border-b border-slate-200 px-6 py-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <div class="tc-label-eyebrow">Sending rules</div>
                            <h2 class="tc-h3 mt-1">What the assistant is allowed to text</h2>
                        </div>
                        <button type="submit" class="tc-btn-primary">Save messaging</button>
                    </div>
                </div>

                <div class="grid gap-4 p-6 md:grid-cols-2 xl:grid-cols-4">
                    <label class="tc-meta-card-white flex min-h-[150px] cursor-pointer flex-col justify-between gap-5 transition hover:border-[rgb(var(--tc-primary)/0.45)]">
                        <span>
                            <span class="block text-sm font-semibold text-slate-950">Auto-send after booking</span>
                            <span class="mt-2 block text-sm leading-6 text-slate-600">When a booking is confirmed, Vapi can send this text once.</span>
                        </span>
                        <span class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                            <input type="checkbox" name="booking_confirmation_enabled" value="1" class="tc-accent-control h-4 w-4 rounded border-slate-300" x-model="bookingEnabled">
                            <span x-text="bookingEnabled ? 'Enabled' : 'Paused'"></span>
                        </span>
                    </label>

                    <label class="tc-meta-card-white flex min-h-[150px] cursor-pointer flex-col justify-between gap-5 transition hover:border-[rgb(var(--tc-primary)/0.45)]">
                        <span>
                            <span class="block text-sm font-semibold text-slate-950">Invite useful replies</span>
                            <span class="mt-2 block text-sm leading-6 text-slate-600">Let callers reply if the appointment time or details need correction.</span>
                        </span>
                        <span class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                            <input type="checkbox" name="reply_capture_enabled" value="1" class="tc-accent-control h-4 w-4 rounded border-slate-300" x-model="replyCapture">
                            <span x-text="replyCapture ? 'Replies on' : 'Replies off'"></span>
                        </span>
                    </label>

                    <label class="tc-meta-card-white flex min-h-[150px] cursor-pointer flex-col justify-between gap-5 transition hover:border-[rgb(var(--tc-primary)/0.45)]">
                        <span>
                            <span class="block text-sm font-semibold text-slate-950">Include ticket number</span>
                            <span class="mt-2 block text-sm leading-6 text-slate-600">Adds the case reference when one exists.</span>
                        </span>
                        <span class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                            <input type="checkbox" name="include_ticket_number" value="1" class="tc-accent-control h-4 w-4 rounded border-slate-300" x-model="includeTicket">
                            <span x-text="includeTicket ? 'Included' : 'Hidden'"></span>
                        </span>
                    </label>

                    <label class="tc-meta-card-white flex min-h-[150px] cursor-pointer flex-col justify-between gap-5 transition hover:border-[rgb(var(--tc-primary)/0.45)]">
                        <span>
                            <span class="block text-sm font-semibold text-slate-950">Include issue label</span>
                            <span class="mt-2 block text-sm leading-6 text-slate-600">Keeps reminders useful without exposing sensitive details.</span>
                        </span>
                        <span class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                            <input type="checkbox" name="include_issue_label" value="1" class="tc-accent-control h-4 w-4 rounded border-slate-300" x-model="includeIssue">
                            <span x-text="includeIssue ? 'Included' : 'Hidden'"></span>
                        </span>
                    </label>
                </div>
            </section>
        </form>

        <div class="grid items-start gap-7 xl:grid-cols-[minmax(0,1fr)_420px]">
            <section class="tc-panel overflow-hidden">
                <div class="border-b border-slate-200 px-6 py-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <div class="tc-label-eyebrow">Message center</div>
                            <h2 class="tc-h3 mt-1">Delivery, responses, and previews</h2>
                        </div>
                        <x-ui.badge tone="slate">{{ $recentMessages->count() }} recent</x-ui.badge>
                    </div>
                </div>

                <div class="p-6">
                    @if($recentMessages->isEmpty())
                        <x-ui.empty-state
                            title="No message history yet"
                            description="Once SMS delivery or reply events are captured, they will appear here with status, assistant, ticket, and caller context."
                        >
                            <div class="grid gap-3 md:grid-cols-3">
                                <div class="tc-meta-card-white text-left">
                                    <div class="tc-label-eyebrow-tight">Status</div>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">Sent, delivered, failed, replied, and click/read proxy events.</p>
                                </div>
                                <div class="tc-meta-card-white text-left">
                                    <div class="tc-label-eyebrow-tight">Responses</div>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">Inbound replies grouped around the original ticket or booking.</p>
                                </div>
                                <div class="tc-meta-card-white text-left">
                                    <div class="tc-label-eyebrow-tight">Preview</div>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">A compact chat-style view for the message thread.</p>
                                </div>
                            </div>
                        </x-ui.empty-state>
                    @else
                        <div class="divide-y divide-slate-200 overflow-hidden rounded-[1.4rem] border border-slate-200 bg-white">
                            @foreach($recentMessages as $message)
                                @php
                                    $contactLabel = $message->contact?->name ?: ($message->to_phone ?: $message->from_phone ?: 'Unknown caller');
                                    $ticketLabel = $message->supportCase?->case_number ?: $message->supportCase?->id;
                                    $messageText = $message->direction === \App\Models\MessageEvent::DIRECTION_INBOUND
                                        ? ($message->response_body ?: $message->body)
                                        : $message->body;
                                @endphp

                                <div class="grid gap-4 p-5 lg:grid-cols-[minmax(0,1fr)_160px]">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <x-ui.badge :tone="\App\Models\MessageEvent::statusTone($message->status)">{{ ucfirst($message->status) }}</x-ui.badge>
                                            <x-ui.badge tone="slate">{{ ucfirst($message->direction) }}</x-ui.badge>
                                            @if($message->assistantConfig)
                                                <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">{{ $message->assistantConfig->name }}</span>
                                            @endif
                                        </div>
                                        <div class="mt-3 text-sm font-semibold text-slate-950">{{ $contactLabel }}</div>
                                        <p class="mt-2 line-clamp-2 text-sm leading-6 text-slate-600">{{ $messageText }}</p>
                                        <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-500">
                                            @if($ticketLabel)
                                                <span>Ticket {{ $ticketLabel }}</span>
                                            @endif
                                            @if($message->calendarEvent?->starts_at)
                                                <span>{{ $message->calendarEvent->starts_at->format('M j, g:i A') }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="text-left text-sm text-slate-500 lg:text-right">
                                        {{ $message->created_at->diffForHumans() }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>

            <div class="space-y-7">
                <section class="tc-panel overflow-hidden">
                    <div class="border-b border-slate-200 px-6 py-5">
                        <div class="tc-label-eyebrow">Automation status</div>
                        <h2 class="tc-h3 mt-1">Readiness</h2>
                    </div>

                    <div class="space-y-3 p-6">
                        <div class="tc-meta-card {{ $settings->booking_confirmation_enabled ? 'border-emerald-200 bg-emerald-50/70' : 'border-slate-200 bg-slate-50/70' }}">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="text-sm font-semibold text-slate-950">Booking confirmations</div>
                                    <div class="mt-1 text-sm text-slate-600">{{ $settings->booking_confirmation_enabled ? 'Assistant can text after booking.' : 'Automatic SMS is off.' }}</div>
                                </div>
                                <x-ui.badge :tone="$settings->booking_confirmation_enabled ? 'success' : 'slate'">{{ $settings->booking_confirmation_enabled ? 'On' : 'Off' }}</x-ui.badge>
                            </div>
                        </div>

                        <div class="tc-meta-card {{ $smsReadyCount > 0 ? 'border-emerald-200 bg-emerald-50/70' : 'border-amber-200 bg-amber-50/70' }}">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="text-sm font-semibold text-slate-950">SMS-ready assistants</div>
                                    <div class="mt-1 text-sm text-slate-600">{{ $smsReadyCount }} assistant(s) have a synced Vapi number.</div>
                                </div>
                                <x-ui.badge :tone="$smsReadyCount > 0 ? 'success' : 'warning'">{{ $smsReadyCount > 0 ? 'Ready' : 'Needed' }}</x-ui.badge>
                            </div>
                        </div>

                        <div class="tc-meta-card {{ $vapiConnected ? 'border-emerald-200 bg-emerald-50/70' : 'border-amber-200 bg-amber-50/70' }}">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="text-sm font-semibold text-slate-950">Vapi SMS tool</div>
                                    <div class="mt-1 text-sm text-slate-600">{{ $vapiConnected ? 'Available for synced assistants.' : 'Missing Vapi API key.' }}</div>
                                </div>
                                <x-ui.badge :tone="$vapiConnected ? 'success' : 'warning'">{{ $vapiConnected ? 'Ready' : 'Missing' }}</x-ui.badge>
                            </div>
                        </div>
                    </div>

                    @if($phoneNumbersLockedForFreePlan)
                        <div class="mx-6 mb-6 rounded-[1.25rem] border border-amber-200 bg-amber-50/80 p-4 text-sm leading-6 text-amber-900">
                            <div class="tc-label-eyebrow text-amber-700">Upgrade required</div>
                            <div class="mt-2">Free workspaces cannot assign live phone numbers, so SMS confirmations stay unavailable until the workspace is upgraded.</div>
                            <div class="mt-4">
                                <a href="{{ route('app.billing.plans') }}" class="tc-btn-primary !px-4 !py-2 text-sm">View plans</a>
                            </div>
                        </div>
                    @endif
                </section>

                <section class="tc-panel overflow-hidden">
                    <div class="border-b border-slate-200 px-6 py-5">
                        <div class="tc-label-eyebrow">Responses</div>
                        <h2 class="tc-h3 mt-1">Needs attention</h2>
                    </div>

                    <div class="p-6">
                        @if($replyMessages->isEmpty())
                            <x-ui.empty-state title="No replies yet" description="When callers reply to confirmations, their responses will appear here for follow-up." />
                        @else
                            <div class="space-y-3">
                                @foreach($replyMessages as $reply)
                                    <div class="tc-meta-card-white">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <div class="text-sm font-semibold text-slate-950">{{ $reply->contact?->name ?: ($reply->from_phone ?: 'Caller') }}</div>
                                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $reply->response_body ?: $reply->body }}</p>
                                            </div>
                                            <span class="text-xs text-slate-400">{{ $reply->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>

                <section class="tc-panel overflow-hidden">
                    <div class="border-b border-slate-200 px-6 py-5">
                        <div class="tc-label-eyebrow">Assistant routing</div>
                        <h2 class="tc-h3 mt-1">Sending lines</h2>
                    </div>

                    <div class="p-6">
                        @if($assistantRows->isEmpty())
                            <x-ui.empty-state
                                title="No assistants yet"
                                description="Create an assistant first. Messaging becomes useful once that assistant has a Vapi-backed number."
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
                                    @endphp
                                    <div class="tc-meta-card-white">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <x-ui.badge :tone="$status['tone']">{{ $status['label'] }}</x-ui.badge>
                                                <div class="mt-3 truncate text-sm font-semibold text-slate-950">{{ $assistant->name }}</div>
                                                <div class="mt-1 text-sm text-slate-600">{{ $phone?->e164 ?: 'No number assigned' }}</div>
                                            </div>
                                            <div class="flex shrink-0 flex-col gap-2">
                                                <a href="{{ route('app.assistant.show', [$workspace, $assistant]) }}" class="tc-accent-link text-sm font-semibold">Edit</a>
                                                <a href="{{ route('app.phone_numbers.index', ['workspace' => $workspace, 'assistant_id' => $assistant->id]) }}" class="tc-accent-link text-sm font-semibold">Number</a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection
