@extends('layouts.marketing', [
    'metaTitle' => $metaTitle,
    'metaDescription' => $metaDescription,
    'metaCanonical' => $metaCanonical,
    'structuredData' => $structuredData,
    'navCurrent' => 'home',
])

@section('content')
    <section class="px-4 py-12 sm:px-5 sm:py-16 lg:px-8 lg:py-20">
        <div class="mx-auto grid max-w-7xl gap-8 lg:grid-cols-[minmax(0,1.08fr)_minmax(0,0.92fr)] lg:items-center">
            <div class="tc-landing-motion-stage" style="--tc-delay: 80ms;">
                <div class="inline-flex rounded-full border border-orange-300/35 bg-orange-500/10 px-4 py-2 text-[0.72rem] font-semibold uppercase tracking-[0.22em] text-orange-200">
                    AI phone answering for real operations
                </div>
                <h1 class="mt-6 max-w-5xl text-5xl font-bold tracking-[-0.05em] text-white sm:text-6xl lg:text-[5.1rem] lg:leading-[0.94]">
                    AI phone answering that turns calls into tickets and booked follow-up.
                </h1>
                <p class="mt-6 max-w-2xl text-[16px] leading-8 text-slate-300 sm:text-[18px]">
                    tickIt answers business calls, captures the issue, saves the transcript, creates the ticket, and helps your team schedule what should happen next.
                </p>

                <div class="mt-10 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('register') }}" class="tc-btn-glow !px-6 !py-3 text-base">Try for Free</a>
                    <a href="{{ route('features.index') }}" class="tc-btn-glass !px-6 !py-3 text-base">Explore features</a>
                </div>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('industries.show', ['page' => 'property-management']) }}" class="rounded-full border border-white/12 bg-white/6 px-4 py-2 text-sm text-slate-200 transition hover:border-white/20 hover:bg-white/10 hover:text-white">
                        Property management
                    </a>
                    <a href="{{ route('industries.show', ['page' => 'reception-and-front-desk']) }}" class="rounded-full border border-white/12 bg-white/6 px-4 py-2 text-sm text-slate-200 transition hover:border-white/20 hover:bg-white/10 hover:text-white">
                        Front desk
                    </a>
                    <a href="{{ route('industries.show', ['page' => 'it-support']) }}" class="rounded-full border border-white/12 bg-white/6 px-4 py-2 text-sm text-slate-200 transition hover:border-white/20 hover:bg-white/10 hover:text-white">
                        IT support
                    </a>
                    <a href="{{ route('industries.show', ['page' => 'service-businesses']) }}" class="rounded-full border border-white/12 bg-white/6 px-4 py-2 text-sm text-slate-200 transition hover:border-white/20 hover:bg-white/10 hover:text-white">
                        Service businesses
                    </a>
                </div>
            </div>

            <div class="tc-landing-panel tc-landing-card tc-landing-motion-stage p-8 sm:p-10" style="--tc-delay: 160ms;">
                <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-orange-300/80">What tickIt does</div>
                <p class="mt-4 text-lg leading-8 text-slate-200">
                    tickIt is AI phone answering software for businesses that need more than a greeting. It captures the caller, issue, urgency, and next action so the team can work from a clear ticket instead of a vague voicemail.
                </p>

                <div class="mt-8 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-[1.35rem] border border-white/10 bg-white/5 p-5">
                        <div class="text-sm font-semibold text-white">Answer the call</div>
                        <p class="mt-2 text-sm leading-6 text-slate-300">Use AI phone answering to greet callers and ask the right intake questions.</p>
                    </div>
                    <div class="rounded-[1.35rem] border border-white/10 bg-white/5 p-5">
                        <div class="text-sm font-semibold text-white">Create the ticket</div>
                        <p class="mt-2 text-sm leading-6 text-slate-300">Turn the call into a ticket with the summary, urgency, transcript, and contact attached.</p>
                    </div>
                    <div class="rounded-[1.35rem] border border-white/10 bg-white/5 p-5">
                        <div class="text-sm font-semibold text-white">Review the transcript</div>
                        <p class="mt-2 text-sm leading-6 text-slate-300">Keep the recording and transcript beside the ticket so the team can review what happened.</p>
                    </div>
                    <div class="rounded-[1.35rem] border border-white/10 bg-white/5 p-5">
                        <div class="text-sm font-semibold text-white">Book the next step</div>
                        <p class="mt-2 text-sm leading-6 text-slate-300">Save or confirm follow-up booking after the request is already logged.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="px-5 py-10 lg:px-8 lg:py-14">
        <div class="mx-auto max-w-7xl">
            <div class="tc-landing-panel tc-landing-card px-6 py-8 sm:px-8">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-3xl">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400">Why teams switch</div>
                        <h2 class="mt-4 text-3xl font-semibold tracking-tight text-white sm:text-4xl">The phone line should create momentum, not admin work.</h2>
                    </div>
                    <p class="max-w-2xl text-sm leading-7 text-slate-300">
                        The biggest gain is not just answering more calls. It is handing the next person a cleaner record with enough context to act quickly.
                    </p>
                </div>

                <div class="mt-8 grid gap-5 lg:grid-cols-3">
                    <div class="rounded-[1.4rem] border border-white/10 bg-white/5 p-6">
                        <div class="text-[0.68rem] font-semibold uppercase tracking-[0.22em] text-orange-300">Step 01</div>
                        <h3 class="mt-4 text-xl font-semibold text-white">The assistant answers and collects the right details</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-300">Caller details, issue summary, urgency, and follow-up context are captured while the conversation is still live.</p>
                    </div>
                    <div class="rounded-[1.4rem] border border-white/10 bg-white/5 p-6">
                        <div class="text-[0.68rem] font-semibold uppercase tracking-[0.22em] text-orange-300">Step 02</div>
                        <h3 class="mt-4 text-xl font-semibold text-white">tickIt creates the ticket and stores the transcript</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-300">The team gets a ticket with the recording, transcript, and caller context attached in one place.</p>
                    </div>
                    <div class="rounded-[1.4rem] border border-white/10 bg-white/5 p-6">
                        <div class="text-[0.68rem] font-semibold uppercase tracking-[0.22em] text-orange-300">Step 03</div>
                        <h3 class="mt-4 text-xl font-semibold text-white">Your team resolves or books the next step</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-300">Support, maintenance, front-desk, and service teams start from a cleaner case instead of calling back blind.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="px-5 py-10 lg:px-8 lg:py-14">
        <div class="mx-auto max-w-7xl">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400">Feature pages</div>
                    <h2 class="mt-4 text-3xl font-semibold tracking-tight text-white sm:text-4xl">High-intent pages for the features people actually search for.</h2>
                </div>
                <a href="{{ route('features.index') }}" class="text-sm font-semibold text-orange-200 transition hover:text-orange-100">See all features</a>
            </div>

            <div class="mt-8 grid gap-5 lg:grid-cols-2">
                @foreach($featureCards as $card)
                    <a href="{{ $card['url'] }}" class="tc-landing-panel tc-landing-card block p-6 transition hover:-translate-y-0.5 hover:border-white/16 hover:bg-white/8">
                        <div class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-400">{{ $card['label'] }}</div>
                        <h3 class="mt-4 text-2xl font-semibold text-white">{{ $card['card_title'] }}</h3>
                        <p class="mt-4 text-sm leading-7 text-slate-300">{{ $card['card_summary'] }}</p>
                        <div class="mt-5 flex flex-wrap gap-2">
                            @foreach(array_slice($card['highlights'], 0, 3) as $highlight)
                                <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-medium text-slate-200">{{ $highlight }}</span>
                            @endforeach
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="px-5 py-10 lg:px-8 lg:py-14">
        <div class="mx-auto max-w-7xl">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400">Industry pages</div>
                    <h2 class="mt-4 text-3xl font-semibold tracking-tight text-white sm:text-4xl">Built for the teams that live on inbound calls.</h2>
                </div>
                <a href="{{ route('industries.index') }}" class="text-sm font-semibold text-orange-200 transition hover:text-orange-100">See all industries</a>
            </div>

            <div class="mt-8 grid gap-5 lg:grid-cols-2">
                @foreach($industryCards as $card)
                    <a href="{{ $card['url'] }}" class="tc-landing-panel tc-landing-card block p-6 transition hover:-translate-y-0.5 hover:border-white/16 hover:bg-white/8">
                        <div class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-400">{{ $card['label'] }}</div>
                        <h3 class="mt-4 text-2xl font-semibold text-white">{{ $card['card_title'] }}</h3>
                        <p class="mt-4 text-sm leading-7 text-slate-300">{{ $card['card_summary'] }}</p>
                        <div class="mt-5 flex flex-wrap gap-2">
                            @foreach(array_slice($card['highlights'], 0, 3) as $highlight)
                                <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-medium text-slate-200">{{ $highlight }}</span>
                            @endforeach
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="px-5 py-10 lg:px-8 lg:py-14">
        <div class="mx-auto grid max-w-7xl gap-5 lg:grid-cols-3">
            <div class="tc-landing-panel tc-landing-card p-8">
                <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400">Why it converts</div>
                <h2 class="mt-4 text-3xl font-semibold tracking-tight text-white">Clearer follow-up for teams and callers.</h2>
                <p class="mt-4 text-sm leading-7 text-slate-300">
                    Visitors do not need to guess what tickIt does. The site now mirrors the decision they are making: feature fit, team fit, and whether the intake will reduce manual work.
                </p>
            </div>
            <div class="rounded-[1.4rem] border border-white/10 bg-white/5 p-6">
                <div class="text-sm font-semibold text-white">Less manual note transfer</div>
                <p class="mt-3 text-sm leading-6 text-slate-300">Calls do not stop at a message slip. They move into a structured ticket with transcript context attached.</p>
            </div>
            <div class="rounded-[1.4rem] border border-white/10 bg-white/5 p-6">
                <div class="text-sm font-semibold text-white">Faster self-qualification</div>
                <p class="mt-3 text-sm leading-6 text-slate-300">Industry and feature pages help visitors decide quickly whether tickIt fits property management, front desk, support, or service workflows.</p>
            </div>
        </div>
    </section>

    <section class="px-5 py-10 lg:px-8 lg:py-14">
        <div class="mx-auto max-w-7xl">
            <div class="tc-landing-panel tc-landing-card p-8">
                <div class="max-w-3xl">
                    <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400">FAQ</div>
                    <h2 class="mt-4 text-3xl font-semibold tracking-tight text-white sm:text-4xl">Questions teams ask when they compare AI phone answering software.</h2>
                    <p class="mt-4 text-base leading-7 text-slate-300">Clear answers for teams comparing AI phone answering, automated ticket creation, and multilingual voice assistants.</p>
                </div>

                <div class="mt-8 grid gap-4 lg:grid-cols-2">
                    @foreach($faqItems as $faq)
                        <div class="rounded-[1.35rem] border border-white/10 bg-white/5 p-5">
                            <h3 class="text-lg font-semibold text-white">{{ $faq['question'] }}</h3>
                            <p class="mt-3 text-sm leading-7 text-slate-300">{{ $faq['answer'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="px-5 pb-10 pt-10 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="tc-landing-panel tc-landing-card tc-landing-glow px-6 py-10 sm:px-10 sm:py-12">
                <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                    <div class="max-w-3xl">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-orange-300/80">Start here</div>
                        <h2 class="mt-4 text-3xl font-semibold tracking-tight text-white sm:text-4xl">Set up your workspace and run one real test call.</h2>
                        <p class="mt-4 text-base leading-7 text-slate-300">
                            That is still the fastest way to understand tickIt. Create the assistant, connect the number, make the call, and watch the ticket appear.
                        </p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('register') }}" class="tc-btn-glow !px-6 !py-3 text-base">Try for Free</a>
                        <a href="{{ route('docs') }}" class="tc-btn-glass !px-6 !py-3 text-base">Read Docs</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
