<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

@php
    $promoCode = 'CALLS20';
    $promoEmail = 'sales@ticketcloser.online';
    $promoParams = [
        'promo' => 'july_operator_offer',
        'discount_code' => $promoCode,
        'utm_source' => 'landing_banner',
        'utm_medium' => 'promo_strip',
        'utm_campaign' => 'july_2026_operator_offer',
    ];
    $promoMailto = 'mailto:' . $promoEmail . '?' . http_build_query([
        'subject' => 'Claim ' . $promoCode . ' for tickIt',
        'body' => 'Hi tickIt team, I want to claim the ' . $promoCode . ' launch offer for my call workflow.',
    ]);
@endphp

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
        <section class="tc-landing-promo relative z-[60] overflow-hidden px-4 py-3 sm:px-6" aria-label="Launch promotion">
            <div class="tc-landing-promo-smoke" aria-hidden="true"></div>
            <div class="relative mx-auto flex max-w-7xl flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                        <span class="tc-landing-promo-kicker">July operator offer</span>
                        <p class="text-sm font-medium leading-6 text-white">
                            20% off your first 3 months, plus a free setup review for the first 25 teams.
                        </p>
                    </div>
                    <p class="mt-1 text-xs leading-5 text-slate-400">
                        Use code <span class="font-semibold text-orange-200">{{ $promoCode }}</span> before July 31, 2026 or email
                        <a href="{{ $promoMailto }}" class="font-semibold text-orange-200 transition hover:text-white">{{ $promoEmail }}</a>.
                    </p>
                </div>

                <form action="{{ route('register') }}" method="GET" class="tc-landing-promo-form" aria-label="Claim launch promotion">
                    @foreach($promoParams as $key => $value)
                        @if(! in_array($key, ['discount_code'], true))
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <label for="promo_email" class="sr-only">Work email</label>
                    <input
                        id="promo_email"
                        name="email"
                        type="email"
                        inputmode="email"
                        autocomplete="email"
                        class="tc-landing-promo-input"
                        placeholder="work email">
                    <label for="promo_discount_code" class="sr-only">Discount code</label>
                    <input
                        id="promo_discount_code"
                        name="discount_code"
                        type="text"
                        class="tc-landing-promo-code"
                        value="{{ $promoCode }}"
                        readonly
                        aria-label="Discount code {{ $promoCode }}">
                    <button type="submit" class="tc-landing-promo-button">Claim offer</button>
                </form>
            </div>
        </section>

        <nav class="tc-landing-nav relative z-50 w-full border-b border-slate-200 bg-white px-6 py-5">
            <div class="relative mx-auto flex max-w-7xl items-center justify-between gap-6">
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <span class="text-[17px] font-bold tracking-tight text-slate-950">tickIt</span>
                </a>

                <div class="absolute left-1/2 hidden -translate-x-1/2 items-center gap-8 lg:flex">
                    <a href="#platform"
                        class="text-[13px] font-medium text-slate-600 transition-colors hover:text-slate-900">Platform</a>
                    <a href="#workflow"
                        class="text-[13px] font-medium text-slate-600 transition-colors hover:text-slate-900">How it works</a>
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
                <div class="mx-auto flex max-w-6xl -translate-y-4 flex-col items-center justify-center text-center sm:-translate-y-6 lg:-translate-y-10">
                    <h1
                        class="tc-landing-motion-stage tc-landing-hero-title max-w-4xl text-5xl font-bold tracking-[-0.05em] text-white sm:text-6xl lg:text-[5.4rem] lg:leading-[0.94]"
                        style="--tc-delay: 90ms;">
                        <span class="tc-landing-readable-copy">tickIt</span>
                    </h1>

                    <p class="tc-landing-motion-stage tc-landing-hero-copy mt-5 max-w-2xl text-[15px] leading-7 text-[#cbd5e1] md:text-[17px]"
                        style="--tc-delay: 180ms;">
                        <span class="tc-landing-readable-copy-subtle">tickIt answers inbound calls, captures caller intent and urgency, saves the transcript, creates the ticket, and keeps showings, maintenance, support, or sales follow-up moving.</span>
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
                        <div class="tc-landing-panel tc-landing-card tc-landing-motion-stage p-8" style="--tc-delay: 60ms;">
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-orange-300/80">
                                What you get</div>
                            <h2 class="mt-5 text-3xl font-semibold tracking-tight text-white sm:text-4xl">Stop losing details between the phone call and the ticket.</h2>
                            <p class="mt-5 max-w-2xl text-base leading-7 text-slate-300">
                                tickIt is built for teams that still run on inbound calls. It captures the caller, the intent, the urgency, and the next step so your team can act from a complete ticket instead of a voicemail and a sticky note.
                            </p>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div class="tc-landing-panel tc-landing-card tc-landing-motion-stage p-6" style="--tc-delay: 120ms;">
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-slate-400">
                                    Call answering</div>
                                <h3 class="mt-4 text-xl font-semibold text-white">Answer the call</h3>
                                <p class="mt-3 text-sm leading-6 text-slate-300">Greet callers and collect the details your team needs before anyone has to call back.</p>
                            </div>
                            <div class="tc-landing-panel tc-landing-card tc-landing-motion-stage p-6" style="--tc-delay: 190ms;">
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-slate-400">
                                    Ticket creation</div>
                                <h3 class="mt-4 text-xl font-semibold text-white">Create the ticket</h3>
                                <p class="mt-3 text-sm leading-6 text-slate-300">Turn every call into a ready-to-work ticket with the summary, urgency, transcript, and contact attached.</p>
                            </div>
                            <div class="tc-landing-panel tc-landing-card tc-landing-motion-stage p-6" style="--tc-delay: 260ms;">
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-slate-400">
                                    Review</div>
                                <h3 class="mt-4 text-xl font-semibold text-white">Review the transcript</h3>
                                <p class="mt-3 text-sm leading-6 text-slate-300">Keep the recording and transcript beside the ticket so the next person has the full story.</p>
                            </div>
                            <div class="tc-landing-panel tc-landing-card tc-landing-motion-stage p-6" style="--tc-delay: 330ms;">
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-slate-400">
                                    Follow-up</div>
                                <h3 class="mt-4 text-xl font-semibold text-white">Book the next step</h3>
                                <p class="mt-3 text-sm leading-6 text-slate-300">Keep the request moving by scheduling the follow-up after the work is already logged.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="workflow" class="px-5 py-10 lg:px-8 lg:py-14">
                <div class="mx-auto max-w-7xl">
                    <div class="tc-landing-panel tc-landing-card tc-landing-motion-stage p-6 sm:p-8" style="--tc-delay: 80ms;">
                        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                            <div class="max-w-2xl">
                                <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400">
                                    How it works</div>
                                <h2 class="mt-4 text-3xl font-semibold tracking-tight text-white sm:text-4xl">Simple from first ring to follow-up.</h2>
                            </div>
                            <p class="max-w-2xl text-sm leading-7 text-slate-300">
                                The biggest gain is not just answering more calls. It is handing the next person a cleaner record with enough context to act quickly.
                            </p>
                        </div>

                        <div class="mt-8 grid gap-5 lg:grid-cols-3">
                            <div class="tc-landing-step-card tc-landing-motion-stage rounded-[1.4rem] border border-white/10 bg-white/5 p-6" style="--tc-delay: 120ms;">
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.22em] text-orange-300">
                                    Step 01</div>
                                <h3 class="mt-4 text-xl font-semibold text-white">The assistant answers and collects the right details</h3>
                                <p class="mt-3 text-sm leading-6 text-slate-300">Caller details, issue summary, urgency, and follow-up context are captured while the conversation is still live.</p>
                            </div>
                            <div class="tc-landing-step-card tc-landing-motion-stage rounded-[1.4rem] border border-white/10 bg-white/5 p-6" style="--tc-delay: 200ms;">
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.22em] text-orange-300">
                                    Step 02</div>
                                <h3 class="mt-4 text-xl font-semibold text-white">tickIt creates the ticket and stores the transcript</h3>
                                <p class="mt-3 text-sm leading-6 text-slate-300">The team gets a ticket with the recording, transcript, and caller context attached in one place.</p>
                            </div>
                            <div class="tc-landing-step-card tc-landing-motion-stage rounded-[1.4rem] border border-white/10 bg-white/5 p-6" style="--tc-delay: 280ms;">
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.22em] text-orange-300">
                                    Step 03</div>
                                <h3 class="mt-4 text-xl font-semibold text-white">Your team resolves or books the next step</h3>
                                <p class="mt-3 text-sm leading-6 text-slate-300">Real estate, support, maintenance, front-desk, and service teams start from a cleaner case instead of calling back blind.</p>
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
                    <div class="tc-landing-panel tc-landing-card tc-landing-motion-stage p-8" style="--tc-delay: 90ms;">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400">Built for
                        </div>
                        <h2 class="mt-4 text-3xl font-semibold tracking-tight text-white">Teams that live on inbound calls.</h2>
                        <ul class="mt-8 space-y-4 text-sm leading-6 text-slate-300">
                            <li class="flex items-start gap-3">
                                <span class="mt-2 h-2 w-2 rounded-full bg-orange-400"></span>
                                Real estate teams capturing showing requests, buyer and seller calls, leasing questions, and after-hours property issues.
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="mt-2 h-2 w-2 rounded-full bg-orange-400"></span>
                                Property teams handling maintenance and resident support.
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="mt-2 h-2 w-2 rounded-full bg-orange-400"></span>
                                Service teams that need clean call notes without manual note-taking.
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="mt-2 h-2 w-2 rounded-full bg-orange-400"></span>
                                Multi-location teams that need separate assistants, numbers, and client accounts.
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="mt-2 h-2 w-2 rounded-full bg-orange-400"></span>
                                Support teams that want better call handling than a basic help desk.
                            </li>
                        </ul>
                    </div>

                    <div class="tc-landing-panel tc-landing-card tc-landing-motion-stage p-8" style="--tc-delay: 170ms;">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400">Why it
                            helps</div>
                        <div class="mt-6 grid gap-4">
                            <div class="tc-landing-step-card tc-landing-motion-stage rounded-[1.35rem] border border-white/10 bg-white/5 p-5" style="--tc-delay: 200ms;">
                                <div class="text-sm font-semibold text-white">Less manual note transfer</div>
                                <p class="mt-2 text-sm leading-6 text-slate-300">Calls do not stop at a message slip. They move into a structured ticket with transcript context attached.</p>
                            </div>
                            <div class="tc-landing-step-card tc-landing-motion-stage rounded-[1.35rem] border border-white/10 bg-white/5 p-5" style="--tc-delay: 270ms;">
                                <div class="text-sm font-semibold text-white">Faster handoff</div>
                                <p class="mt-2 text-sm leading-6 text-slate-300">The next person gets the caller, the issue, the summary, and the transcript in one place.</p>
                            </div>
                            <div class="tc-landing-step-card tc-landing-motion-stage rounded-[1.35rem] border border-white/10 bg-white/5 p-5" style="--tc-delay: 340ms;">
                                <div class="text-sm font-semibold text-white">Cleaner follow-up</div>
                                <p class="mt-2 text-sm leading-6 text-slate-300">Your team can resolve the request or book what happens next without reconstructing the call first.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="faq" class="px-5 py-10 lg:px-8 lg:py-14">
                <div class="mx-auto max-w-7xl">
                    <div class="tc-landing-panel tc-landing-card tc-landing-motion-stage p-8" style="--tc-delay: 110ms;">
                        <div class="max-w-3xl">
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400">FAQ</div>
                            <h2 class="mt-4 text-3xl font-semibold tracking-tight text-white sm:text-4xl">Questions teams ask when they compare AI phone answering software.</h2>
                            <p class="mt-4 text-base leading-7 text-slate-300">Clear answers for teams comparing AI phone answering, automated ticket creation, and multilingual voice assistants.</p>
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
                                <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-orange-300/80">
                                    Start here</div>
                                <h2 class="mt-4 text-3xl font-semibold tracking-tight text-white sm:text-4xl">Set up your account and run one real test call.</h2>
                                <p class="mt-4 text-base leading-7 text-slate-300">
                                    Create the assistant, connect the number, make the call, and watch the ticket appear.
                                </p>
                            </div>

                            <div class="flex flex-col gap-3 sm:flex-row">
                                <a href="{{ route('register') }}" class="tc-btn-glow !px-6 !py-3 text-base">Try for Free</a>
                                <a href="{{ route('login') }}" class="tc-btn-glass !px-6 !py-3 text-base">Sign in</a>
                            </div>
                        </div>
                    </div>

                </div>
            </section>

        </main>
    </div>
<!-- Scaffolding credit banner -->
<style>
@import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@500;600;700&display=swap');
.sdv-credit{position:relative;z-index:50;background:#000;border-top:1px solid rgba(255,255,255,.10);padding:18px 20px;display:flex;align-items:center;justify-content:center;width:100%;box-sizing:border-box;}
.sdv-credit a{display:inline-flex;align-items:center;gap:11px;text-decoration:none;opacity:.72;transition:opacity .2s ease;flex-wrap:wrap;justify-content:center;}
.sdv-credit a:hover{opacity:1;}
.sdv-credit__icon{width:32px;height:32px;flex:none;background:#000;border:1px solid rgba(255,255,255,.16);border-radius:9px;display:inline-flex;align-items:center;justify-content:center;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Courier New",monospace;font-size:13px;font-weight:700;color:#fff;}
.sdv-credit__mark{font-family:"Orbitron","Space Grotesk",system-ui,sans-serif;text-transform:uppercase;letter-spacing:.14em;font-size:12.5px;font-weight:600;color:#fff;}
.sdv-credit__bar{width:1px;height:15px;background:rgba(255,255,255,.18);}
.sdv-credit__sub{font-family:Inter,system-ui,-apple-system,Segoe UI,sans-serif;font-size:11.5px;letter-spacing:.01em;color:rgba(255,255,255,.46);}
</style>
<div class="sdv-credit">
<a href="https://scaffoldingdev.com" target="_blank" rel="noopener noreferrer" aria-label="Designed and developed by Scaffolding">
<span class="sdv-credit__icon">&gt;_</span>
<span class="sdv-credit__mark">Scaffolding</span>
<span class="sdv-credit__bar"></span>
<span class="sdv-credit__sub">Designed &amp; developed by scaffolding.ai</span>
</a>
</div>
</body>

</html>
