@extends('layouts.saas')

@section('title', 'tickIt - Messaging')
@section('header_eyebrow', 'SMS follow-up')
@section('header', 'Messaging')
@section('header_description', 'Customize the texts callers receive, monitor delivery, and review replies from one workspace inbox.')

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
        $replyMessages = $recentMessages->filter(fn ($message) => $message->direction === \App\Models\MessageEvent::DIRECTION_INBOUND || filled($message->response_body))->take(4);
    @endphp

    <div class="space-y-6">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stat-card label="Sent messages" :value="$sentCount" hint="Outbound confirmations" tone="blue" />
            <x-ui.stat-card label="Delivery rate" :value="$deliveryRate.'%'" :hint="$deliveredCount.' delivered'" tone="emerald" />
            <x-ui.stat-card label="Open / read proxy" :value="$openProxyRate.'%'" hint="Clicks, replies, or provider reads" tone="orange" />
            <x-ui.stat-card label="Reply rate" :value="$replyRate.'%'" :hint="$replyCount.' replies captured'" tone="amber" />
        </div>

        <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1.12fr)_minmax(360px,0.88fr)]">
            <div class="space-y-6">
                <x-ui.panel title="Customize messages" description="This template is added to your Vapi assistant instructions and used after a meeting is booked.">
                    <form
                        method="POST"
                        action="{{ route('app.messaging.update', $workspace) }}"
                        class="space-y-6"
                        x-data="{
                            template: @js($templateValue),
                            signature: @js($signatureValue),
                            bookingEnabled: @js($bookingEnabledValue),
                            includeTicket: @js($includeTicketValue),
                            includeIssue: @js($includeIssueValue),
                            replyCapture: @js($replyCaptureValue),
                            renderPreview() {
                                let output = this.template || '';
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
                        @csrf

                        <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_330px]">
                            <div class="space-y-5">
                                <div class="tc-field">
                                    <label for="booking_confirmation_template" class="tc-field-label">Booking confirmation text</label>
                                    <textarea
                                        id="booking_confirmation_template"
                                        name="booking_confirmation_template"
                                        rows="5"
                                        class="tc-textarea"
                                        x-model="template"
                                        placeholder="Hi @{{customer_name}}, your follow-up is booked for @{{appointment_time}}."
                                    >{{ $templateValue }}</textarea>
                                    <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                        @foreach(['customer_name', 'workspace_name', 'appointment_time', 'ticket_number', 'issue_label', 'signature'] as $token)
                                            <span class="tc-pill-mini border border-slate-200 bg-slate-50 text-slate-600">{!! '&#123;&#123;'.$token.'&#125;&#125;' !!}</span>
                                        @endforeach
                                    </div>
                                    @error('booking_confirmation_template')
                                        <p class="tc-error">{{ $message }}</p>
                                    @enderror
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

                                <div class="grid gap-3 md:grid-cols-2">
                                    <label class="tc-meta-card-white flex cursor-pointer items-start gap-3">
                                        <input type="checkbox" name="booking_confirmation_enabled" value="1" class="mt-1 rounded border-slate-300 text-orange-600 focus:ring-orange-500" x-model="bookingEnabled">
                                        <span>
                                            <span class="block text-sm font-semibold text-slate-950">Auto-send after booking</span>
                                            <span class="mt-1 block text-sm leading-6 text-slate-600">When `bookMeeting` succeeds, Vapi can send this confirmation once.</span>
                                        </span>
                                    </label>

                                    <label class="tc-meta-card-white flex cursor-pointer items-start gap-3">
                                        <input type="checkbox" name="reply_capture_enabled" value="1" class="mt-1 rounded border-slate-300 text-orange-600 focus:ring-orange-500" x-model="replyCapture">
                                        <span>
                                            <span class="block text-sm font-semibold text-slate-950">Invite useful replies</span>
                                            <span class="mt-1 block text-sm leading-6 text-slate-600">Let callers reply if the appointment time or details need correction.</span>
                                        </span>
                                    </label>

                                    <label class="tc-meta-card-white flex cursor-pointer items-start gap-3">
                                        <input type="checkbox" name="include_ticket_number" value="1" class="mt-1 rounded border-slate-300 text-orange-600 focus:ring-orange-500" x-model="includeTicket">
                                        <span>
                                            <span class="block text-sm font-semibold text-slate-950">Include ticket number</span>
                                            <span class="mt-1 block text-sm leading-6 text-slate-600">Adds the case reference when one exists.</span>
                                        </span>
                                    </label>

                                    <label class="tc-meta-card-white flex cursor-pointer items-start gap-3">
                                        <input type="checkbox" name="include_issue_label" value="1" class="mt-1 rounded border-slate-300 text-orange-600 focus:ring-orange-500" x-model="includeIssue">
                                        <span>
                                            <span class="block text-sm font-semibold text-slate-950">Include issue label</span>
                                            <span class="mt-1 block text-sm leading-6 text-slate-600">Keeps the reminder useful without exposing sensitive details.</span>
                                        </span>
                                    </label>
                                </div>
                            </div>

                            <div class="rounded-[1.6rem] border border-slate-200 bg-slate-950 p-4 text-white shadow-[0_24px_70px_-34px_rgba(15,23,42,0.45)]">
                                <div class="mb-4 flex items-center justify-between gap-3">
                                    <div>
                                        <div class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-400">Chat preview</div>
                                        <div class="mt-1 text-sm font-semibold">Caller phone</div>
                                    </div>
                                    <x-ui.badge :tone="$readyForMessaging ? 'success' : 'warning'">{{ $readyForMessaging ? 'Live' : 'Setup needed' }}</x-ui.badge>
                                </div>

                                <div class="space-y-3 rounded-[1.25rem] bg-white/7 p-4">
                                    <div class="max-w-[82%] rounded-2xl rounded-bl-md bg-white/10 px-4 py-3 text-sm leading-6 text-slate-200">
                                        Thanks, Tuesday at 2:30 works.
                                    </div>
                                    <div class="ml-auto max-w-[88%] rounded-2xl rounded-br-md bg-orange-500 px-4 py-3 text-sm font-medium leading-6 text-white shadow-[0_18px_40px_-24px_rgba(249,115,22,0.75)]">
                                        <span x-text="renderPreview()">{{ $messagePreview }}</span>
                                    </div>
                                </div>

                                <div class="mt-4 flex items-center justify-between gap-3 text-xs text-slate-400">
                                    <span x-text="remaining() + ' chars left before 320'"></span>
                                    <span>{{ $settings->updated_at ? 'Updated '.$settings->updated_at->diffForHumans() : 'Default template' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-sm leading-6 text-slate-600">
                                SMS does not provide a universal true open rate. We track delivery plus click, reply, or provider-read signals as the open/read proxy.
                            </p>
                            <button type="submit" class="tc-btn-primary">Save messaging</button>
                        </div>
                    </form>
                </x-ui.panel>

                <x-ui.panel title="Message center" description="Delivery status, responses, and caller conversation previews.">
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
                        <div class="space-y-3">
                            @foreach($recentMessages as $message)
                                @php
                                    $contactLabel = $message->contact?->name ?: ($message->to_phone ?: $message->from_phone ?: 'Unknown caller');
                                    $ticketLabel = $message->supportCase?->case_number ?: $message->supportCase?->id;
                                @endphp

                                <div class="tc-meta-card-strong">
                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <x-ui.badge :tone="\App\Models\MessageEvent::statusTone($message->status)">{{ ucfirst($message->status) }}</x-ui.badge>
                                                <x-ui.badge tone="slate">{{ ucfirst($message->direction) }}</x-ui.badge>
                                                @if($message->assistantConfig)
                                                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">{{ $message->assistantConfig->name }}</span>
                                                @endif
                                            </div>
                                            <div class="mt-3 text-sm font-semibold text-slate-950">{{ $contactLabel }}</div>
                                            <p class="mt-2 line-clamp-2 text-sm leading-6 text-slate-600">{{ $message->direction === \App\Models\MessageEvent::DIRECTION_INBOUND ? ($message->response_body ?: $message->body) : $message->body }}</p>
                                            <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-500">
                                                @if($ticketLabel)
                                                    <span>Ticket {{ $ticketLabel }}</span>
                                                @endif
                                                @if($message->calendarEvent?->starts_at)
                                                    <span>{{ $message->calendarEvent->starts_at->format('M j, g:i A') }}</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="text-sm text-slate-500">
                                            {{ $message->created_at->diffForHumans() }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-ui.panel>
            </div>

            <div class="space-y-6 xl:sticky xl:top-6 xl:self-start">
                <x-ui.panel title="Automation status" description="What is live, what is waiting, and what needs setup.">
                    <div class="space-y-3">
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
                        <div class="mt-4 rounded-[1.25rem] border border-amber-200 bg-amber-50/80 p-4 text-sm leading-6 text-amber-900">
                            <div class="tc-label-eyebrow text-amber-700">Upgrade required</div>
                            <div class="mt-2">Free workspaces cannot assign live phone numbers, so SMS confirmations stay unavailable until the workspace is upgraded.</div>
                            <div class="mt-4">
                                <a href="{{ route('app.billing.plans') }}" class="tc-btn-primary !px-4 !py-2 text-sm">View plans</a>
                            </div>
                        </div>
                    @endif
                </x-ui.panel>

                <x-ui.panel title="Responses" description="Replies that need human attention.">
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
                </x-ui.panel>

                <x-ui.panel title="Assistant routing" description="Each assistant follows the same path: synced assistant plus active Vapi number.">
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
                                            <div class="flex flex-wrap items-center gap-2">
                                                <x-ui.badge :tone="$status['tone']">{{ $status['label'] }}</x-ui.badge>
                                            </div>
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
                </x-ui.panel>
            </div>
        </div>
    </div>
@endsection
