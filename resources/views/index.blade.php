<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.seo.meta', [
        'title' => $metaTitle,
        'description' => $metaDescription,
        'canonical' => $metaCanonical,
        'robots' => 'index,follow',
        'structuredData' => $structuredData,
    ])
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    @include('partials.analytics.google-tag')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body style="background:#020202;color:#fff;overflow-x:hidden" class="font-[Inter,system-ui,sans-serif] antialiased">
    <canvas id="three-canvas"
        style="position:fixed;top:0;left:0;width:100%;height:100%;z-index:0;pointer-events:none"></canvas>

    <div class="tc-landing-shell">
        <nav class="tc-landing-nav relative z-50 w-full border-b border-slate-200 bg-white px-6 py-5">
            <div class="relative mx-auto flex max-w-7xl items-center justify-between gap-6">
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <span class="text-[17px] font-bold tracking-tight text-slate-950">tickIt</span>
                </a>

                <div class="absolute left-1/2 hidden -translate-x-1/2 items-center gap-8 lg:flex">
                    <a href="#platform"
                        class="text-[13px] font-medium text-slate-600 transition-colors hover:text-slate-900">Platform</a>
                    <a href="#workflow"
                        class="text-[13px] font-medium text-slate-600 transition-colors hover:text-slate-900">Workflow</a>
                    <a href="{{ route('docs') }}"
                        class="text-[13px] font-medium text-slate-600 transition-colors hover:text-slate-900">Docs</a>
                    <a href="#operations"
                        class="text-[13px] font-medium text-slate-600 transition-colors hover:text-slate-900">Operations</a>
                </div>

                <div class="flex items-center gap-6">
                    @auth
                        <a href="{{ url('/dashboard') }}"
                            class="text-[13px] font-medium text-slate-600 transition-colors hover:text-slate-900">dashboard</a>
                    @else
                        <a href="{{ route('login') }}"
                            class="text-[13px] font-medium text-slate-600 transition-colors hover:text-slate-900">Sign
                            in</a>
                        <a href="{{ route('register') }}"
                            class="rounded-lg bg-[#f97316] px-4 py-2 text-[13px] font-medium text-white transition-colors hover:bg-[#ea580c]">
                            Try for Free
                        </a>
                    @endauth
                </div>
            </div>
        </nav>

        <main class="relative z-10 overflow-hidden">
            @php
                $proofItems = ['Property desks', 'Service teams', 'Support lines', 'Maintenance ops', 'Sales intake'];
                $journeyCards = [
                    [
                        'eyebrow' => 'Answer',
                        'title' => 'Pick up when the team cannot.',
                        'body' => 'Your assistant greets the caller, listens for intent, and keeps the conversation moving without rushing them.',
                    ],
                    [
                        'eyebrow' => 'Capture',
                        'title' => 'Turn the call into structured context.',
                        'body' => 'Caller details, urgency, transcript, summary, and follow-up notes land together instead of scattering across tools.',
                    ],
                    [
                        'eyebrow' => 'Route',
                        'title' => 'Send the right work to the right place.',
                        'body' => 'Sales, support, and maintenance requests can route into the correct assistant, ticket queue, or calendar flow.',
                    ],
                ];
                $integrations = ['Vapi', 'Azure Speech', 'Google Calendar', 'Twilio ready', 'Webhooks', 'Email', 'CSV export', 'CRM handoff', 'Slack'];
                $capabilities = [
                    ['title' => 'Multilingual voices', 'body' => 'Configure assistants for English, Spanish, German, French, and other high-use languages.'],
                    ['title' => 'Human timing', 'body' => 'Preset speed, pauses, and interruption handling to avoid robotic or pushy calls.'],
                    ['title' => 'Call transcripts', 'body' => 'Keep recordings and transcripts attached to the ticket for fast review.'],
                    ['title' => 'Number routing', 'body' => 'Connect new numbers or forward existing ones into the assistant workflow.'],
                    ['title' => 'Workspace controls', 'body' => 'Keep teams, numbers, assistants, tickets, and billing separated cleanly.'],
                    ['title' => 'Follow-up booking', 'body' => 'Move from intake to a calendar-ready next step without another manual handoff.'],
                ];
                $metrics = [
                    ['value' => '24/7', 'label' => 'phone coverage'],
                    ['value' => '1', 'label' => 'ticket per call'],
                    ['value' => '3 min', 'label' => 'number activation window'],
                    ['value' => '0', 'label' => 'sticky notes needed'],
                ];
            @endphp

            <section class="px-5 pb-16 pt-12 sm:pb-20 sm:pt-16 lg:px-8 lg:pb-24 lg:pt-20">
                <div class="mx-auto max-w-7xl">
                    <div class="tc-landing-motion-stage mx-auto mb-8 flex max-w-fit items-center gap-2 rounded-full border border-white/10 bg-white/[0.06] px-3 py-1.5 text-[0.7rem] font-semibold uppercase tracking-[0.24em] text-slate-300 backdrop-blur"
                        style="--tc-delay: 40ms;">
                        <span class="h-1.5 w-1.5 rounded-full bg-orange-400 shadow-[0_0_18px_rgba(251,146,60,0.72)]"></span>
                        AI phone intake for real teams
                    </div>

                    <div class="grid items-center gap-8 lg:grid-cols-[minmax(0,0.92fr)_minmax(0,1.08fr)]">
                        <div class="max-w-3xl">
                            <h1 class="tc-landing-motion-stage tc-landing-hero-title text-5xl font-bold tracking-[-0.055em] text-white sm:text-6xl lg:text-[5.8rem] lg:leading-[0.9]"
                                style="--tc-delay: 100ms;">
                                <span class="tc-landing-readable-copy">AI calls into tickets.</span>
                            </h1>

                            <p class="tc-landing-motion-stage tc-landing-hero-copy mt-6 max-w-2xl text-base leading-7 text-slate-300 sm:text-[1.05rem]"
                                style="--tc-delay: 190ms;">
                                <span class="tc-landing-readable-copy-subtle">tickIt answers inbound calls, captures what happened, creates the ticket, and keeps the follow-up moving.</span>
                            </p>

                            <div class="tc-landing-motion-stage tc-landing-hero-actions mt-8 flex flex-col gap-3 sm:flex-row"
                                style="--tc-delay: 280ms;">
                                <a href="{{ route('register') }}"
                                    class="tc-landing-cta-primary inline-flex min-w-[9.5rem] justify-center rounded-xl bg-[#f97316] px-5 py-3 text-[15px] font-semibold text-white shadow-[0_0_24px_rgba(249,115,22,0.38)] transition hover:bg-[#ea580c]">
                                    Try for Free
                                </a>
                                <a href="#workflow"
                                    class="tc-landing-cta-secondary inline-flex min-w-[9.5rem] justify-center rounded-xl border border-white/12 bg-slate-950/28 px-5 py-3 text-[15px] font-semibold text-white/90 backdrop-blur-sm transition hover:border-white/18 hover:bg-white/10 hover:text-white">
                                    See workflow
                                </a>
                            </div>

                            <div class="tc-landing-motion-stage mt-8 grid max-w-xl gap-3 sm:grid-cols-3"
                                style="--tc-delay: 360ms;">
                                <div class="rounded-2xl border border-white/10 bg-white/[0.045] p-4 backdrop-blur">
                                    <div class="text-[0.65rem] font-semibold uppercase tracking-[0.22em] text-slate-500">Use for</div>
                                    <div class="mt-2 text-sm font-semibold text-white">Sales</div>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/[0.045] p-4 backdrop-blur">
                                    <div class="text-[0.65rem] font-semibold uppercase tracking-[0.22em] text-slate-500">Use for</div>
                                    <div class="mt-2 text-sm font-semibold text-white">Support</div>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/[0.045] p-4 backdrop-blur">
                                    <div class="text-[0.65rem] font-semibold uppercase tracking-[0.22em] text-slate-500">Use for</div>
                                    <div class="mt-2 text-sm font-semibold text-white">Maintenance</div>
                                </div>
                            </div>
                        </div>

                        <div class="tc-landing-panel tc-landing-card tc-landing-demo-frame tc-landing-motion-stage p-3 sm:p-4"
                            style="--tc-delay: 160ms;">
                            <div class="relative overflow-hidden rounded-[1.55rem] border border-white/10 bg-slate-950/78">
                                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-orange-300/70 to-transparent"></div>
                                <div class="flex items-center justify-between border-b border-white/10 px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="h-2.5 w-2.5 rounded-full bg-red-400/80"></span>
                                        <span class="h-2.5 w-2.5 rounded-full bg-amber-300/80"></span>
                                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-300/80"></span>
                                    </div>
                                    <span class="rounded-full border border-emerald-300/20 bg-emerald-300/10 px-3 py-1 text-[0.65rem] font-semibold uppercase tracking-[0.2em] text-emerald-200">Live intake</span>
                                </div>

                                <div class="grid gap-4 p-4 md:grid-cols-[minmax(0,0.72fr)_minmax(0,1fr)] md:p-5">
                                    <div class="tc-landing-voice-orb min-h-[19rem] rounded-[1.35rem] border border-white/10 bg-black/40 p-5">
                                        <div class="text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-slate-500">Assistant</div>
                                        <div class="mt-5 flex justify-center">
                                            <div class="tc-landing-pulse-ring relative flex h-36 w-36 items-center justify-center rounded-full border border-orange-300/20 bg-orange-400/10">
                                                <div class="h-20 w-20 rounded-full bg-gradient-to-br from-orange-300 to-orange-600 shadow-[0_0_42px_rgba(249,115,22,0.45)]"></div>
                                            </div>
                                        </div>
                                        <div class="mt-7 rounded-2xl border border-white/10 bg-white/[0.055] p-4">
                                            <div class="text-sm font-semibold text-white">Property intake</div>
                                            <p class="mt-2 text-sm leading-6 text-slate-300">Calm voice, short questions, no interruption.</p>
                                        </div>
                                    </div>

                                    <div class="space-y-4">
                                        <div class="tc-landing-step-card rounded-[1.35rem] border border-white/10 bg-white/[0.055] p-4">
                                            <div class="flex items-center justify-between gap-4">
                                                <div>
                                                    <div class="text-[0.68rem] font-semibold uppercase tracking-[0.22em] text-slate-500">Caller says</div>
                                                    <p class="mt-2 text-sm leading-6 text-slate-200">"There is water under the kitchen sink."</p>
                                                </div>
                                                <span class="rounded-full bg-orange-400/15 px-3 py-1 text-xs font-semibold text-orange-200">Urgent</span>
                                            </div>
                                        </div>

                                        <div class="tc-landing-route-line relative overflow-hidden rounded-[1.35rem] border border-white/10 bg-white/[0.045] p-4">
                                            <div class="text-[0.68rem] font-semibold uppercase tracking-[0.22em] text-slate-500">Ticket created</div>
                                            <h3 class="mt-3 text-xl font-semibold tracking-tight text-white">Leak under kitchen sink</h3>
                                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                                <div class="rounded-2xl bg-black/30 p-3">
                                                    <div class="text-[0.65rem] uppercase tracking-[0.2em] text-slate-500">Caller</div>
                                                    <div class="mt-1 text-sm font-semibold text-white">+1 216 ...</div>
                                                </div>
                                                <div class="rounded-2xl bg-black/30 p-3">
                                                    <div class="text-[0.65rem] uppercase tracking-[0.2em] text-slate-500">Source</div>
                                                    <div class="mt-1 text-sm font-semibold text-white">Voice</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="rounded-[1.35rem] border border-white/10 bg-white/[0.055] p-4">
                                            <div class="flex flex-col gap-3 sm:flex-row">
                                                <div class="flex-1 rounded-xl border border-white/10 bg-black/30 px-3 py-3 text-sm text-slate-300">Maintenance intake</div>
                                                <a href="{{ route('register') }}"
                                                    class="tc-landing-cta-primary rounded-xl bg-[#f97316] px-4 py-3 text-center text-sm font-semibold text-white shadow-[0_0_22px_rgba(249,115,22,0.34)]">
                                                    Run test call
                                                </a>
                                            </div>
                                            <p class="mt-3 text-xs leading-5 text-slate-500">Choose a language, call path, and assistant before going live.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="px-5 py-8 lg:px-8">
                <div class="mx-auto max-w-7xl">
                    <div class="tc-landing-panel tc-landing-motion-stage overflow-hidden rounded-[1.75rem] p-0"
                        style="--tc-delay: 80ms;">
                        <div class="grid gap-0 lg:grid-cols-[minmax(0,0.95fr)_minmax(0,1.05fr)]">
                            <div class="border-b border-white/10 p-6 sm:p-8 lg:border-b-0 lg:border-r">
                                <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-orange-300/80">Built around the handoff</div>
                                <p class="mt-4 max-w-2xl text-xl font-semibold leading-8 tracking-tight text-white sm:text-2xl">"The best call is not just answered. It leaves the next person with the whole story."</p>
                            </div>
                            <div class="relative overflow-hidden p-6 sm:p-8">
                                <div class="tc-landing-logo-track flex min-w-max gap-3">
                                    @foreach (array_merge($proofItems, $proofItems) as $item)
                                        <span class="rounded-full border border-white/10 bg-white/[0.055] px-4 py-2 text-sm font-semibold text-slate-300">{{ $item }}</span>
                                    @endforeach
                                </div>
                                <div class="pointer-events-none absolute inset-y-0 left-0 w-20 bg-gradient-to-r from-slate-950/90 to-transparent"></div>
                                <div class="pointer-events-none absolute inset-y-0 right-0 w-20 bg-gradient-to-l from-slate-950/90 to-transparent"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="platform" class="px-5 py-12 lg:px-8 lg:py-16">
                <div class="mx-auto max-w-7xl">
                    <div class="tc-landing-motion-stage mx-auto max-w-3xl text-center" style="--tc-delay: 90ms;">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400">Platform</div>
                        <h2 class="mt-4 text-4xl font-semibold tracking-[-0.045em] text-white sm:text-5xl">One place for calls, tickets, transcripts, and next steps.</h2>
                        <p class="mt-5 text-base leading-7 text-slate-300">The interface stays simple: configure the assistant, connect a number, review the call, and work the ticket.</p>
                    </div>

                    <div class="mt-12 grid gap-5">
                        <div class="grid gap-5 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
                            <div class="tc-landing-panel tc-landing-card tc-landing-motion-stage p-7 sm:p-8" style="--tc-delay: 120ms;">
                                <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-orange-300/80">Configure</div>
                                <h3 class="mt-5 text-3xl font-semibold tracking-tight text-white">Build an assistant by role, language, and behavior.</h3>
                                <p class="mt-4 text-sm leading-7 text-slate-300">Choose tone, speed, interruption rules, opening line, prompt, and call routing without rebuilding the same assistant from scratch every time.</p>
                            </div>

                            <div class="tc-landing-panel tc-landing-card tc-landing-dashboard-card tc-landing-motion-stage p-4 sm:p-5" style="--tc-delay: 190ms;">
                                <div class="rounded-[1.45rem] border border-white/10 bg-black/35 p-4">
                                    <div class="flex items-center justify-between gap-4">
                                        <div>
                                            <div class="text-[0.68rem] font-semibold uppercase tracking-[0.22em] text-slate-500">Assistant setup</div>
                                            <h4 class="mt-2 text-xl font-semibold text-white">Sales intake - English</h4>
                                        </div>
                                        <span class="rounded-full border border-emerald-300/20 bg-emerald-300/10 px-3 py-1 text-xs font-semibold text-emerald-200">Ready</span>
                                    </div>
                                    <div class="mt-5 grid gap-3 sm:grid-cols-3">
                                        <div class="rounded-2xl border border-white/10 bg-white/[0.045] p-4">
                                            <div class="text-[0.65rem] uppercase tracking-[0.2em] text-slate-500">Language</div>
                                            <div class="mt-2 text-sm font-semibold text-white">German</div>
                                        </div>
                                        <div class="rounded-2xl border border-white/10 bg-white/[0.045] p-4">
                                            <div class="text-[0.65rem] uppercase tracking-[0.2em] text-slate-500">Tone</div>
                                            <div class="mt-2 text-sm font-semibold text-white">Warm</div>
                                        </div>
                                        <div class="rounded-2xl border border-white/10 bg-white/[0.045] p-4">
                                            <div class="text-[0.65rem] uppercase tracking-[0.2em] text-slate-500">Pace</div>
                                            <div class="mt-2 text-sm font-semibold text-white">Human</div>
                                        </div>
                                    </div>
                                    <div class="mt-5 rounded-2xl border border-white/10 bg-white/[0.045] p-4">
                                        <div class="mb-3 flex items-center justify-between">
                                            <span class="text-sm font-semibold text-white">Opening line</span>
                                            <span class="text-xs text-slate-500">Translated automatically</span>
                                        </div>
                                        <p class="text-sm leading-6 text-slate-300">"Thanks for calling. Tell me how I can help, and I will route you to the right team."</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-5 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
                            <div class="tc-landing-panel tc-landing-card tc-landing-dashboard-card tc-landing-motion-stage order-2 p-4 sm:p-5 lg:order-1" style="--tc-delay: 160ms;">
                                <div class="rounded-[1.45rem] border border-white/10 bg-black/35 p-4">
                                    <div class="flex items-center justify-between gap-4 border-b border-white/10 pb-4">
                                        <div>
                                            <div class="text-[0.68rem] font-semibold uppercase tracking-[0.22em] text-slate-500">Recent ticket</div>
                                            <h4 class="mt-2 text-xl font-semibold text-white">Sink leak with sewage smell</h4>
                                        </div>
                                        <span class="rounded-full bg-red-400/12 px-3 py-1 text-xs font-semibold text-red-200">Critical</span>
                                    </div>
                                    <div class="mt-4 grid gap-4 lg:grid-cols-[minmax(0,0.92fr)_minmax(0,1.08fr)]">
                                        <div class="rounded-2xl border border-white/10 bg-white/[0.045] p-4">
                                            <div class="text-[0.65rem] uppercase tracking-[0.2em] text-slate-500">Summary</div>
                                            <p class="mt-3 text-sm leading-6 text-slate-300">Caller reports active leak under kitchen sink. Tenant available today after 4 PM.</p>
                                        </div>
                                        <div class="rounded-2xl border border-white/10 bg-white/[0.045] p-4">
                                            <div class="text-[0.65rem] uppercase tracking-[0.2em] text-slate-500">Transcript preview</div>
                                            <div class="mt-3 space-y-2 text-sm leading-6 text-slate-300">
                                                <p><span class="text-slate-500">Caller:</span> The cabinet is wet.</p>
                                                <p><span class="text-slate-500">Assistant:</span> I can help. Is water still running?</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="tc-landing-panel tc-landing-card tc-landing-motion-stage order-1 p-7 sm:p-8 lg:order-2" style="--tc-delay: 230ms;">
                                <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-orange-300/80">Operate</div>
                                <h3 class="mt-5 text-3xl font-semibold tracking-tight text-white">Review the exact call context before your team acts.</h3>
                                <p class="mt-4 text-sm leading-7 text-slate-300">Calls should not become mystery tasks. tickIt keeps the call, transcript, ticket, contact, and next move beside each other.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="workflow" class="px-5 py-12 lg:px-8 lg:py-16">
                <div class="mx-auto max-w-7xl">
                    <div class="grid gap-8 lg:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)] lg:items-start">
                        <div class="tc-landing-motion-stage lg:sticky lg:top-10" style="--tc-delay: 80ms;">
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400">Workflow</div>
                            <h2 class="mt-4 text-4xl font-semibold tracking-[-0.045em] text-white sm:text-5xl">From first ring to next move.</h2>
                            <p class="mt-5 max-w-xl text-base leading-7 text-slate-300">A tight call path keeps callers from getting stuck, then gives your team the structured context to finish the job.</p>
                            <div class="mt-7 flex flex-wrap gap-3">
                                <span class="rounded-full border border-white/10 bg-white/[0.055] px-4 py-2 text-sm font-semibold text-slate-300">Caller details</span>
                                <span class="rounded-full border border-white/10 bg-white/[0.055] px-4 py-2 text-sm font-semibold text-slate-300">Intent routing</span>
                                <span class="rounded-full border border-white/10 bg-white/[0.055] px-4 py-2 text-sm font-semibold text-slate-300">Ticket handoff</span>
                            </div>
                        </div>

                        <div class="relative border-l border-white/10 pl-6 sm:pl-8">
                            @foreach ($journeyCards as $card)
                                <article class="tc-landing-step-card tc-landing-motion-stage relative mb-5 rounded-[1.5rem] border border-white/10 bg-slate-950/36 p-6 backdrop-blur-xl last:mb-0"
                                    style="--tc-delay: {{ 120 + ($loop->index * 90) }}ms;">
                                    <div class="absolute -left-[2.42rem] top-6 flex h-8 w-8 items-center justify-center rounded-full border border-orange-300/30 bg-slate-950 text-xs font-semibold text-orange-200 shadow-[0_0_24px_rgba(249,115,22,0.25)] sm:-left-[2.92rem]">{{ str_pad((string) ($loop->index + 1), 2, '0', STR_PAD_LEFT) }}</div>
                                    <div class="text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-slate-500">{{ $card['eyebrow'] }}</div>
                                    <h3 class="mt-3 text-2xl font-semibold tracking-tight text-white">{{ $card['title'] }}</h3>
                                    <p class="mt-3 text-sm leading-7 text-slate-300">{{ $card['body'] }}</p>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            <section class="px-5 py-12 lg:px-8 lg:py-16">
                <div class="mx-auto max-w-7xl">
                    <div class="tc-landing-panel tc-landing-card tc-landing-motion-stage overflow-hidden p-0" style="--tc-delay: 90ms;">
                        <div class="grid gap-0 lg:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)]">
                            <div class="border-b border-white/10 p-7 sm:p-9 lg:border-b-0 lg:border-r">
                                <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-orange-300/80">Integrations</div>
                                <h2 class="mt-4 text-4xl font-semibold tracking-[-0.045em] text-white sm:text-5xl">Wire phone calls into the tools you already use.</h2>
                                <p class="mt-5 text-base leading-7 text-slate-300">Keep the star of the workflow simple: answer, classify, create, route, and follow up.</p>
                            </div>
                            <div class="p-6 sm:p-8">
                                <div class="grid gap-3 sm:grid-cols-3">
                                    @foreach ($integrations as $integration)
                                        <div class="tc-landing-step-card tc-landing-motion-stage flex min-h-[6.5rem] items-end rounded-[1.35rem] border border-white/10 bg-white/[0.055] p-4"
                                            style="--tc-delay: {{ 150 + ($loop->index * 35) }}ms;">
                                            <span class="text-sm font-semibold text-white">{{ $integration }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="operations" class="px-5 py-12 lg:px-8 lg:py-16">
                <div class="mx-auto grid max-w-7xl gap-8 lg:grid-cols-[minmax(0,0.78fr)_minmax(0,1.22fr)]">
                    <div class="tc-landing-motion-stage" style="--tc-delay: 80ms;">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400">Operations</div>
                        <h2 class="mt-4 text-4xl font-semibold tracking-[-0.045em] text-white sm:text-5xl">Designed for the unglamorous work that keeps teams moving.</h2>
                        <p class="mt-5 max-w-xl text-base leading-7 text-slate-300">No novelty circus. Just the call details, route, record, and next step your team needs.</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ($capabilities as $capability)
                            <article class="tc-landing-panel tc-landing-card tc-landing-motion-stage p-6" style="--tc-delay: {{ 130 + ($loop->index * 55) }}ms;">
                                <h3 class="text-xl font-semibold tracking-tight text-white">{{ $capability['title'] }}</h3>
                                <p class="mt-3 text-sm leading-7 text-slate-300">{{ $capability['body'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="px-5 py-12 lg:px-8 lg:py-16">
                <div class="mx-auto max-w-7xl">
                    <div class="grid gap-5 lg:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)]">
                        <div class="tc-landing-panel tc-landing-card tc-landing-motion-stage min-h-[27rem] p-8 sm:p-10" style="--tc-delay: 100ms;">
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-orange-300/80">Example outcome</div>
                            <h2 class="mt-5 max-w-3xl text-4xl font-semibold tracking-[-0.045em] text-white sm:text-5xl">A caller explains the issue once. The team gets the record once.</h2>
                            <p class="mt-6 max-w-2xl text-base leading-7 text-slate-300">That is the conversion point: not a flashier phone tree, but fewer dropped details between the call and the person who handles the work.</p>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-1">
                            <div class="tc-landing-panel tc-landing-card tc-landing-motion-stage p-8" style="--tc-delay: 170ms;">
                                <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400">Before</div>
                                <p class="mt-5 text-2xl font-semibold leading-9 tracking-tight text-white">Voicemail, sticky note, missed urgency, second call.</p>
                            </div>
                            <div class="tc-landing-panel tc-landing-card tc-landing-motion-stage p-8" style="--tc-delay: 240ms;">
                                <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400">After</div>
                                <p class="mt-5 text-2xl font-semibold leading-9 tracking-tight text-white">Ticket, transcript, urgency, contact, next move.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($metrics as $metric)
                            <div class="tc-landing-panel tc-landing-card tc-landing-motion-stage p-6" style="--tc-delay: {{ 120 + ($loop->index * 60) }}ms;">
                                <div class="text-4xl font-semibold tracking-[-0.05em] text-white">{{ $metric['value'] }}</div>
                                <div class="mt-2 text-sm font-medium text-slate-400">{{ $metric['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section id="faq" class="px-5 py-12 lg:px-8 lg:py-16">
                <div class="mx-auto max-w-7xl">
                    <div class="tc-landing-motion-stage mx-auto max-w-3xl text-center" style="--tc-delay: 80ms;">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400">FAQ</div>
                        <h2 class="mt-4 text-4xl font-semibold tracking-[-0.045em] text-white sm:text-5xl">Questions teams ask before moving calls into AI.</h2>
                    </div>

                    <div class="mt-10 grid gap-4 lg:grid-cols-2">
                        @foreach($faqItems as $faq)
                            <div class="tc-landing-panel tc-landing-card tc-landing-motion-stage p-6"
                                style="--tc-delay: {{ 130 + ($loop->index * 55) }}ms;">
                                <h3 class="text-lg font-semibold text-white">{{ $faq['question'] }}</h3>
                                <p class="mt-3 text-sm leading-7 text-slate-300">{{ $faq['answer'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="px-5 pb-24 pt-12 lg:px-8">
                <div class="mx-auto max-w-7xl">
                    <div class="tc-landing-panel tc-landing-card tc-landing-glow tc-landing-motion-stage overflow-hidden px-6 py-12 sm:px-10 sm:py-14"
                        style="--tc-delay: 100ms;">
                        <div class="relative grid gap-8 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                            <div class="max-w-3xl">
                                <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-orange-300/80">Start building</div>
                                <h2 class="mt-4 text-4xl font-semibold tracking-[-0.045em] text-white sm:text-5xl">Run one real test call.</h2>
                                <p class="mt-5 text-base leading-7 text-slate-300">Create the assistant, connect the number, make the call, and watch the ticket appear.</p>
                            </div>

                            <div class="flex flex-col gap-3 sm:flex-row">
                                <a href="{{ route('register') }}" class="tc-btn-glow !px-6 !py-3 text-base">Start Free</a>
                                <a href="{{ route('login') }}" class="tc-btn-glass !px-6 !py-3 text-base">Sign in</a>
                            </div>
                        </div>
                    </div>

                    <footer
                        class="mt-8 grid gap-6 border-t border-white/10 pt-6 text-sm text-slate-400 sm:grid-cols-[minmax(0,1fr)_auto_auto] sm:items-center">
                        <div>tickIt turns inbound calls into tickets your team can work.</div>
                        <a href="{{ route('docs') }}" class="transition hover:text-white">Docs</a>
                        <div>&copy; {{ date('Y') }} tickIt</div>
                    </footer>
                </div>
            </section>
        </main>
    </div>
</body>

</html>
