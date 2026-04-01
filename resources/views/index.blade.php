<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TicketCloser</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body style="background:#020202;color:#fff;overflow-x:hidden"
    class="font-[Inter,system-ui,sans-serif] antialiased selection:bg-orange-500/20">
    <canvas id="three-canvas"
        style="position:fixed;top:0;left:0;width:100%;height:100%;z-index:0;pointer-events:none"></canvas>

    <div class="tc-landing-shell">
        <nav class="relative z-50 w-full border-b border-slate-200 bg-white px-6 py-5">
            <div class="relative mx-auto flex max-w-7xl items-center justify-between gap-6">
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <span class="text-[17px] font-bold tracking-tight text-slate-900">ticketcloser</span>
                </a>

                <div class="absolute left-1/2 hidden -translate-x-1/2 items-center gap-8 lg:flex">
                    <a href="#platform"
                        class="text-[13px] font-medium text-slate-600 transition-colors hover:text-slate-900">Platform</a>
                    <a href="#workflow"
                        class="text-[13px] font-medium text-slate-600 transition-colors hover:text-slate-900">Workflow</a>
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
                            Get started free
                        </a>
                    @endauth
                </div>
            </div>
        </nav>

        <main class="relative z-10">
            <section class="px-4 py-10 sm:px-5 sm:py-14 lg:px-8 lg:py-18">
                <div class="mx-auto flex min-h-[68vh] max-w-6xl flex-col items-center justify-center text-center">
                    <!-- <div class="text-[0.75rem] font-semibold uppercase tracking-[0.3em] text-slate-300">
                        ticketcloser
                    </div> -->

                    <h1 class="mt-5 max-w-5xl text-5xl font-bold tracking-tight text-orange-500 sm:text-6xl lg:text-7xl">
                        ticketcloser
                    </h1>

                    <p class="mt-5 max-w-2xl text-[15px] font-normal text-[#cbd5e1] md:text-[17px]">
                        Capture support calls, structure the details, and move every request into a cleaner operational
                        workflow.
                    </p>

                    <div class="mt-10 flex w-full flex-col items-center gap-4 sm:w-auto sm:flex-row">
                        <a href="{{ route('register') }}"
                            class="w-full rounded-xl bg-[#f97316] px-6 py-3 text-[15px] font-medium text-white shadow-[0_0_24px_rgba(249,115,22,0.38)] transition hover:bg-[#ea580c] hover:shadow-[0_0_34px_rgba(249,115,22,0.54)] sm:w-auto">
                            Create your own Voice Assistant ->
                        </a>
                    </div>
                </div>
            </section>

            <section id="platform" class="px-5 py-10 lg:px-8 lg:py-14">
                <div class="mx-auto max-w-7xl">
                    <div class="grid gap-5 lg:grid-cols-[minmax(0,0.95fr)_minmax(0,1.05fr)]">
                        <div class="tc-landing-panel p-8">
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-orange-300/80">
                                Platform value</div>
                            <h2 class="mt-5 text-3xl font-semibold tracking-tight text-white sm:text-4xl">A calmer
                                operating layer for support teams that live on the phone.</h2>
                            <p class="mt-5 max-w-2xl text-base leading-7 text-slate-300">
                                Reduce intake friction, keep call outcomes legible, and move from conversation to action
                                without losing context.
                            </p>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div class="tc-landing-panel p-6">
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-slate-400">
                                    Inbound intake</div>
                                <h3 class="mt-4 text-xl font-semibold text-white">Collect the right information on the
                                    first call.</h3>
                                <p class="mt-3 text-sm leading-6 text-slate-300">Use prompts, required fields,
                                    escalations, and caller metadata without making the conversation feel robotic.</p>
                            </div>
                            <div class="tc-landing-panel p-6">
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-slate-400">
                                    Ticket operations</div>
                                <h3 class="mt-4 text-xl font-semibold text-white">Move from transcript to ticket without
                                    cleanup work.</h3>
                                <p class="mt-3 text-sm leading-6 text-slate-300">Cases arrive with title, summary,
                                    priority, source, and status already structured.</p>
                            </div>
                            <div class="tc-landing-panel p-6">
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-slate-400">
                                    Assistant management</div>
                                <h3 class="mt-4 text-xl font-semibold text-white">Run multiple assistants across
                                    different workflows.</h3>
                                <p class="mt-3 text-sm leading-6 text-slate-300">Configure prompts, numbers, fallbacks,
                                    and sync state from one workspace.</p>
                            </div>
                            <div class="tc-landing-panel p-6">
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-slate-400">
                                    Operational review</div>
                                <h3 class="mt-4 text-xl font-semibold text-white">See recent calls, outcomes, and
                                    follow-up in one system.</h3>
                                <p class="mt-3 text-sm leading-6 text-slate-300">Track volume, review transcripts,
                                    monitor meetings, and spot what still needs action.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="workflow" class="px-5 py-10 lg:px-8 lg:py-14">
                <div class="mx-auto max-w-7xl">
                    <div class="tc-landing-panel p-6 sm:p-8">
                        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                            <div class="max-w-2xl">
                                <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400">
                                    Workflow</div>
                                <h2 class="mt-4 text-3xl font-semibold tracking-tight text-white sm:text-4xl">Built
                                    around the operational path your team actually follows.</h2>
                            </div>
                            <p class="max-w-2xl text-sm leading-7 text-slate-300">
                                Keep the handoff between caller, assistant, and internal team intact from the first
                                sentence to final resolution.
                            </p>
                        </div>

                        <div class="mt-8 grid gap-5 lg:grid-cols-3">
                            <div class="rounded-[1.4rem] border border-white/10 bg-white/5 p-6">
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.22em] text-orange-300">
                                    Step 01</div>
                                <h3 class="mt-4 text-xl font-semibold text-white">A caller reaches your assistant</h3>
                                <p class="mt-3 text-sm leading-6 text-slate-300">The assistant captures intent, urgency,
                                    callback details, and issue context in real time.</p>
                            </div>
                            <div class="rounded-[1.4rem] border border-white/10 bg-white/5 p-6">
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.22em] text-orange-300">
                                    Step 02</div>
                                <h3 class="mt-4 text-xl font-semibold text-white">The system creates an actionable case
                                </h3>
                                <p class="mt-3 text-sm leading-6 text-slate-300">Teams get a clean record immediately,
                                    with transcript context and operational fields already in place.</p>
                            </div>
                            <div class="rounded-[1.4rem] border border-white/10 bg-white/5 p-6">
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.22em] text-orange-300">
                                    Step 03</div>
                                <h3 class="mt-4 text-xl font-semibold text-white">Teams resolve, escalate, or schedule
                                    next steps</h3>
                                <p class="mt-3 text-sm leading-6 text-slate-300">Use built-in scheduling and cleaner
                                    status flow so requests do not get lost.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="operations" class="px-5 py-10 lg:px-8 lg:py-14">
                <div class="mx-auto grid max-w-7xl gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                    <div class="tc-landing-panel p-8">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400">Where it
                            fits</div>
                        <h2 class="mt-4 text-3xl font-semibold tracking-tight text-white">Designed for teams with repeat
                            support volume and real operational accountability.</h2>
                        <ul class="mt-8 space-y-4 text-sm leading-6 text-slate-300">
                            <li class="flex items-start gap-3">
                                <span class="mt-2 h-2 w-2 rounded-full bg-orange-400"></span>
                                Property management teams handling maintenance and resident support.
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="mt-2 h-2 w-2 rounded-full bg-orange-400"></span>
                                Service organizations that need structured phone intake without manual transcription
                                work.
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="mt-2 h-2 w-2 rounded-full bg-orange-400"></span>
                                Multi-tenant operations that require separate assistants, phone numbers, and workspaces.
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="mt-2 h-2 w-2 rounded-full bg-orange-400"></span>
                                Support teams that want a more premium operating system than a generic helpdesk shell.
                            </li>
                        </ul>
                    </div>

                    <div class="tc-landing-panel p-8">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400">Why teams
                            switch</div>
                        <div class="mt-6 grid gap-4">
                            <div class="rounded-[1.35rem] border border-white/10 bg-white/5 p-5">
                                <div class="text-sm font-semibold text-white">Fewer missed details</div>
                                <p class="mt-2 text-sm leading-6 text-slate-300">Required fields and prompts keep key
                                    intake context from disappearing between call and case.</p>
                            </div>
                            <div class="rounded-[1.35rem] border border-white/10 bg-white/5 p-5">
                                <div class="text-sm font-semibold text-white">Cleaner team handoff</div>
                                <p class="mt-2 text-sm leading-6 text-slate-300">Recent activity, transcripts, and
                                    summaries stay together so teams can act without hunting.</p>
                            </div>
                            <div class="rounded-[1.35rem] border border-white/10 bg-white/5 p-5">
                                <div class="text-sm font-semibold text-white">Better tenant and workspace control</div>
                                <p class="mt-2 text-sm leading-6 text-slate-300">Separate workspaces, assistant
                                    settings, integrations, and billing as the operation grows.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="px-5 pb-24 pt-10 lg:px-8">
                <div class="mx-auto max-w-7xl">
                    <div class="tc-landing-panel tc-landing-glow px-6 py-10 sm:px-10 sm:py-12">
                        <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                            <div class="max-w-3xl">
                                <div
                                    class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-orange-300/80">
                                    Start with a real workspace</div>
                                <h2 class="mt-4 text-3xl font-semibold tracking-tight text-white sm:text-4xl">Upgrade
                                    your support intake without sacrificing clarity or operational taste.</h2>
                                <p class="mt-4 text-base leading-7 text-slate-300">
                                    Set up your workspace, sync an assistant, provision a number, and turn calls into
                                    structured action.
                                </p>
                            </div>

                            <div class="flex flex-col gap-3 sm:flex-row">
                                <a href="{{ route('register') }}" class="tc-btn-glow !px-6 !py-3 text-base">Create a
                                    workspace</a>
                                <a href="{{ route('login') }}" class="tc-btn-glass !px-6 !py-3 text-base">Sign in</a>
                            </div>
                        </div>
                    </div>

                    <footer
                        class="mt-8 flex flex-col gap-4 border-t border-white/10 pt-6 text-sm text-slate-400 sm:flex-row sm:items-center sm:justify-between">
                        <div>TicketCloser builds a more intentional operating layer for voice-first support.</div>
                        <div>&copy; {{ date('Y') }} TicketCloser</div>
                    </footer>
                </div>
            </section>
        </main>
    </div>
</body>

</html>