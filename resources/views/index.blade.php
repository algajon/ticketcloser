<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    @php
        $metaTitle = 'tickIt | AI Phone Answering, Ticket Creation, and Meeting Booking';
        $metaDescription = 'tickIt helps businesses automate phone calls with AI answering, ticket creation, transcripts, contact capture, and meeting booking for property management, reception, maintenance, and support teams.';
        $metaCanonical = route('home');
        $faqItems = [
            [
                'question' => 'What does tickIt do?',
                'answer' => 'tickIt answers business phone calls, captures the details, creates a ticket, saves the transcript, and can help move follow-up or meeting booking forward.',
            ],
            [
                'question' => 'Is tickIt good for property management and maintenance requests?',
                'answer' => 'Yes. tickIt is a strong fit for property management teams that need to capture resident calls, maintenance requests, urgency, and callback details without losing information.',
            ],
            [
                'question' => 'Can tickIt work for reception and front desk teams?',
                'answer' => 'Yes. tickIt can answer calls, capture the reason for the call, create a ticket, and help route the next step for reception and front desk workflows.',
            ],
            [
                'question' => 'Can tickIt help IT support teams?',
                'answer' => 'Yes. tickIt can turn inbound support calls into structured tickets with transcripts, caller details, and follow-up context for IT support teams.',
            ],
        ];
        $structuredData = [
            [
                '@context' => 'https://schema.org',
                '@type' => 'SoftwareApplication',
                'name' => 'tickIt',
                'applicationCategory' => 'BusinessApplication',
                'applicationSubCategory' => 'AI phone answering and ticketing software',
                'operatingSystem' => 'Web',
                'url' => $metaCanonical,
                'description' => $metaDescription,
                'offers' => [
                    '@type' => 'Offer',
                    'price' => '0',
                    'priceCurrency' => 'EUR',
                    'description' => 'Free trial available',
                ],
                'audience' => [
                    '@type' => 'Audience',
                    'audienceType' => 'Property management teams, reception teams, support teams, and service businesses',
                ],
            ],
            [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => collect($faqItems)->map(fn ($item) => [
                    '@type' => 'Question',
                    'name' => $item['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $item['answer'],
                    ],
                ])->values()->all(),
            ],
        ];
    @endphp
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

<body style="background:#020202;color:#fff;overflow-x:hidden"
    class="font-[Inter,system-ui,sans-serif] antialiased">
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

        <main class="relative z-10">
            <section
                class="flex min-h-[calc(100vh-5.5rem+4rem)] items-center px-4 py-12 sm:min-h-[calc(100vh-5.5rem+5rem)] sm:px-5 sm:py-16 lg:min-h-[calc(100vh-5.75rem+5rem)] lg:px-8 lg:py-20">
                <div class="mx-auto flex max-w-6xl flex-col items-center justify-center text-center">
                    <h1
                        class="tc-landing-motion-stage tc-landing-hero-title mt-5 max-w-4xl text-5xl font-bold tracking-[-0.05em] text-white sm:text-6xl lg:text-[5.4rem] lg:leading-[0.94]"
                        style="--tc-delay: 90ms;">
                        <span class="tc-landing-readable-copy">Never miss another call with tickIt.</span>
                    </h1>

                    <p
                        class="tc-landing-motion-stage tc-landing-hero-copy mt-5 max-w-2xl text-[15px] leading-7 text-[#cbd5e1] md:text-[17px]"
                        style="--tc-delay: 180ms;">
                        <span class="tc-landing-readable-copy-subtle">tickIt answers the phone for you, captures the
                            details, creates a ticket, and books meetings for you.</span>
                    </p>

                    <div class="tc-landing-motion-stage tc-landing-hero-actions mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row"
                        style="--tc-delay: 270ms;">
                        <a href="{{ route('register') }}"
                            class="tc-landing-cta-primary inline-flex min-w-[10.5rem] justify-center rounded-xl bg-[#f97316] px-6 py-3 text-[15px] font-semibold text-white shadow-[0_0_24px_rgba(249,115,22,0.38)] transition hover:bg-[#ea580c] hover:shadow-[0_0_34px_rgba(249,115,22,0.54)]">
                            Try for Free
                        </a>
                        <a href="#workflow"
                            class="tc-landing-cta-secondary inline-flex min-w-[10.5rem] justify-center rounded-xl border border-white/12 bg-slate-950/24 px-6 py-3 text-[15px] font-semibold text-white/92 shadow-[0_22px_60px_-38px_rgba(15,23,42,0.9)] backdrop-blur-sm transition hover:border-white/18 hover:bg-white/10 hover:text-white">
                            See how it works
                        </a>
                    </div>
                </div>
            </section>

            <section id="platform" class="px-5 pb-10 pt-28 lg:px-8 lg:pb-14 lg:pt-32">
                <div class="mx-auto max-w-7xl">
                    <div class="grid gap-5 lg:grid-cols-[minmax(0,0.95fr)_minmax(0,1.05fr)]">
                        <div class="tc-landing-panel tc-landing-card tc-landing-motion-stage p-8"
                            style="--tc-delay: 60ms;">
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-orange-300/80">
                                What you get</div>
                            <h2 class="mt-5 text-3xl font-semibold tracking-tight text-white sm:text-4xl">One place for
                                business calls, tickets, and follow-up.</h2>
                            <p class="mt-5 max-w-2xl text-base leading-7 text-slate-300">
                                Stop switching between a phone line, notes, and a help desk. tickIt keeps AI phone
                                answering, ticket creation, transcripts, and meeting follow-up in one place.
                            </p>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div class="tc-landing-panel tc-landing-card tc-landing-motion-stage p-6"
                                style="--tc-delay: 120ms;">
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-slate-400">
                                    Call intake</div>
                                <h3 class="mt-4 text-xl font-semibold text-white">Ask the right questions on every business call.
                                </h3>
                                <p class="mt-3 text-sm leading-6 text-slate-300">Capture the caller, the issue, the
                                    urgency, and the callback details automatically.</p>
                            </div>
                            <div class="tc-landing-panel tc-landing-card tc-landing-motion-stage p-6"
                                style="--tc-delay: 190ms;">
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-slate-400">
                                    Ticket creation</div>
                                <h3 class="mt-4 text-xl font-semibold text-white">Turn every call into a ready-to-work
                                    ticket.</h3>
                                <p class="mt-3 text-sm leading-6 text-slate-300">Create the title, summary, priority,
                                    and contact record right away.</p>
                            </div>
                            <div class="tc-landing-panel tc-landing-card tc-landing-motion-stage p-6"
                                style="--tc-delay: 260ms;">
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-slate-400">
                                    Assistant control</div>
                                <h3 class="mt-4 text-xl font-semibold text-white">Manage assistants from one workspace.
                                </h3>
                                <p class="mt-3 text-sm leading-6 text-slate-300">Change prompts, numbers, fallback
                                    rules, and live status in one place.</p>
                            </div>
                            <div class="tc-landing-panel tc-landing-card tc-landing-motion-stage p-6"
                                style="--tc-delay: 330ms;">
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-slate-400">
                                    Review</div>
                                <h3 class="mt-4 text-xl font-semibold text-white">See what happened after the call.</h3>
                                <p class="mt-3 text-sm leading-6 text-slate-300">Review recordings, transcripts,
                                    meetings, and ticket status without switching tools.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="workflow" class="px-5 py-10 lg:px-8 lg:py-14">
                <div class="mx-auto max-w-7xl">
                    <div class="tc-landing-panel tc-landing-card tc-landing-motion-stage p-6 sm:p-8"
                        style="--tc-delay: 80ms;">
                        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                            <div class="max-w-2xl">
                                <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400">
                                    Workflow</div>
                                <h2 class="mt-4 text-3xl font-semibold tracking-tight text-white sm:text-4xl">Simple
                                    from first ring to follow-up.</h2>
                            </div>
                            <p class="max-w-2xl text-sm leading-7 text-slate-300">
                                The caller talks once. Your team gets the record and knows what to do next.
                            </p>
                        </div>

                        <div class="mt-8 grid gap-5 lg:grid-cols-3">
                            <div class="tc-landing-step-card tc-landing-motion-stage rounded-[1.4rem] border border-white/10 bg-white/5 p-6"
                                style="--tc-delay: 120ms;">
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.22em] text-orange-300">
                                    Step 01</div>
                                <h3 class="mt-4 text-xl font-semibold text-white">The caller talks to your assistant
                                </h3>
                                <p class="mt-3 text-sm leading-6 text-slate-300">It collects the issue, urgency, and
                                    callback details in real time.</p>
                            </div>
                            <div class="tc-landing-step-card tc-landing-motion-stage rounded-[1.4rem] border border-white/10 bg-white/5 p-6"
                                style="--tc-delay: 200ms;">
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.22em] text-orange-300">
                                    Step 02</div>
                                <h3 class="mt-4 text-xl font-semibold text-white">tickIt creates the ticket</h3>
                                <p class="mt-3 text-sm leading-6 text-slate-300">Your team gets a clean record right
                                    away, with the call context already attached.</p>
                            </div>
                            <div class="tc-landing-step-card tc-landing-motion-stage rounded-[1.4rem] border border-white/10 bg-white/5 p-6"
                                style="--tc-delay: 280ms;">
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.22em] text-orange-300">
                                    Step 03</div>
                                <h3 class="mt-4 text-xl font-semibold text-white">Your team resolves or books next steps
                                </h3>
                                <p class="mt-3 text-sm leading-6 text-slate-300">Use statuses and scheduling so nothing
                                    gets stuck or lost.</p>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-center">
                            <a href="{{ route('docs') }}" class="tc-btn-glass !px-6 !py-3 text-base">
                                See Docs
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <section id="operations" class="px-5 py-10 lg:px-8 lg:py-14">
                <div class="mx-auto grid max-w-7xl gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                    <div class="tc-landing-panel tc-landing-card tc-landing-motion-stage p-8"
                        style="--tc-delay: 90ms;">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400">Best for
                        </div>
                        <h2 class="mt-4 text-3xl font-semibold tracking-tight text-white">Built for teams that live on
                            inbound calls.</h2>
                        <ul class="mt-8 space-y-4 text-sm leading-6 text-slate-300">
                            <li class="flex items-start gap-3">
                                <span class="mt-2 h-2 w-2 rounded-full bg-orange-400"></span>
                                Property teams handling maintenance and resident support.
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="mt-2 h-2 w-2 rounded-full bg-orange-400"></span>
                                Service teams that need clean phone intake without manual note-taking.
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="mt-2 h-2 w-2 rounded-full bg-orange-400"></span>
                                Multi-location teams that need separate assistants, numbers, and workspaces.
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="mt-2 h-2 w-2 rounded-full bg-orange-400"></span>
                                Support teams that want a better call workflow than a basic help desk.
                            </li>
                        </ul>
                    </div>

                    <div class="tc-landing-panel tc-landing-card tc-landing-motion-stage p-8"
                        style="--tc-delay: 170ms;">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400">Why it
                            helps</div>
                        <div class="mt-6 grid gap-4">
                            <div class="tc-landing-step-card tc-landing-motion-stage rounded-[1.35rem] border border-white/10 bg-white/5 p-5"
                                style="--tc-delay: 200ms;">
                                <div class="text-sm font-semibold text-white">Fewer missed details</div>
                                <p class="mt-2 text-sm leading-6 text-slate-300">Important call details do not disappear
                                    between the phone call and the ticket.</p>
                            </div>
                            <div class="tc-landing-step-card tc-landing-motion-stage rounded-[1.35rem] border border-white/10 bg-white/5 p-5"
                                style="--tc-delay: 270ms;">
                                <div class="text-sm font-semibold text-white">Faster handoff</div>
                                <p class="mt-2 text-sm leading-6 text-slate-300">Calls, transcripts, and summaries stay
                                    together so the next person can act fast.</p>
                            </div>
                            <div class="tc-landing-step-card tc-landing-motion-stage rounded-[1.35rem] border border-white/10 bg-white/5 p-5"
                                style="--tc-delay: 340ms;">
                                <div class="text-sm font-semibold text-white">Cleaner growth</div>
                                <p class="mt-2 text-sm leading-6 text-slate-300">Keep each company or team separate as
                                    your operation grows.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="faq" class="px-5 py-10 lg:px-8 lg:py-14">
                <div class="mx-auto max-w-7xl">
                    <div class="tc-landing-panel tc-landing-card tc-landing-motion-stage p-8"
                        style="--tc-delay: 110ms;">
                        <div class="max-w-3xl">
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400">FAQ</div>
                            <h2 class="mt-4 text-3xl font-semibold tracking-tight text-white sm:text-4xl">Questions teams ask before they try tickIt.</h2>
                            <p class="mt-4 text-base leading-7 text-slate-300">Clear answers for businesses comparing AI phone answering, receptionist automation, and automated ticket creation.</p>
                        </div>

                        <div class="mt-8 grid gap-4 lg:grid-cols-2">
                            @foreach($faqItems as $faq)
                                <div class="tc-landing-step-card tc-landing-motion-stage rounded-[1.35rem] border border-white/10 bg-white/5 p-5"
                                    style="--tc-delay: {{ 170 + ($loop->index * 60) }}ms;">
                                    <h3 class="text-lg font-semibold text-white">{{ $faq['question'] }}</h3>
                                    <p class="mt-3 text-sm leading-7 text-slate-300">{{ $faq['answer'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            <section class="px-5 pb-24 pt-10 lg:px-8">
                <div class="mx-auto max-w-7xl">
                    <div class="tc-landing-panel tc-landing-card tc-landing-glow tc-landing-motion-stage px-6 py-10 sm:px-10 sm:py-12"
                        style="--tc-delay: 100ms;">
                        <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                            <div class="max-w-3xl">
                                <div
                                    class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-orange-300/80">
                                    Start here</div>
                                <h2 class="mt-4 text-3xl font-semibold tracking-tight text-white sm:text-4xl">Set up
                                    your workspace and run a real test call.</h2>
                                <p class="mt-4 text-base leading-7 text-slate-300">
                                    You can get an assistant live, connect a number, and see the whole flow in minutes.
                                </p>
                            </div>

                            <div class="flex flex-col gap-3 sm:flex-row">
                                <a href="{{ route('register') }}" class="tc-btn-glow !px-6 !py-3 text-base">Try for Free</a>
                                <a href="{{ route('login') }}" class="tc-btn-glass !px-6 !py-3 text-base">Sign in</a>
                            </div>
                        </div>
                    </div>

                    <footer
                        class="mt-8 flex flex-col gap-4 border-t border-white/10 pt-6 text-sm text-slate-400 sm:flex-row sm:items-center sm:justify-between">
                        <div>tickIt turns inbound calls into tickets your team can work.</div>
                        <div>&copy; {{ date('Y') }} tickIt</div>
                    </footer>
                </div>
            </section>
        </main>
    </div>
</body>

</html>
