@php
    $routeName = Route::currentRouteName() ?? '';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ticketcloser</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .btn-glow {
            box-shadow: 0 0 20px rgba(249, 115, 22, 0.4);
            transition: all 0.3s ease;
        }

        .btn-glow:hover {
            box-shadow: 0 0 30px rgba(249, 115, 22, 0.6);
        }
    </style>
</head>

<body style="background:#020202;color:#fff;overflow-x:hidden"
    class="font-[Inter,system-ui,sans-serif] antialiased selection:bg-orange-500/30">

    {{-- Three.js canvas underlay --}}
    <canvas id="three-canvas"
        style="position:fixed;top:0;left:0;width:100%;height:100%;z-index:0;pointer-events:none"></canvas>

    {{-- Navigation --}}
    <nav class="relative z-50 w-full px-6 py-5 bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <a href="/" class="flex items-center gap-2">
                <span class="text-[17px] font-bold tracking-tight text-slate-900">ticketcloser</span>
            </a>
            <div class="flex items-center gap-6">
                @auth
                    <a href="{{ url('/dashboard') }}"
                        class="text-[13px] font-medium text-slate-600 hover:text-slate-900 transition-colors">dashboard</a>
                @else
                    <a href="{{ route('login') }}"
                        class="text-[13px] font-medium text-slate-600 hover:text-slate-900 transition-colors">Sign in</a>
                    <a href="{{ route('register') }}"
                        class="text-[13px] font-medium px-4 py-2 bg-[#f97316] hover:bg-[#ea580c] rounded-lg text-white transition-colors">
                        Get started free
                    </a>
                @endauth
            </div>
        </div>
    </nav>

    {{-- Content Wrapper ensuring everything sits cleanly over the canvas --}}
    <div class="relative z-10 w-full flex flex-col items-center">

        {{-- Hero Section --}}
        <main class="w-full flex flex-col items-center justify-center min-h-[75vh] px-4 text-center">
            <h1 class="text-6xl md:text-7xl font-bold tracking-tight backdrop-blur-sm mb-4 text-white lowercase">
                ticketcloser
            </h1>

            <p class="text-[15px] md:text-[17px] text-[#cbd5e1] max-w-xl mx-auto mb-10 font-normal">
                Turn every phone call into a structured support ticket, automatically.
            </p>

            <div class="flex flex-col sm:flex-row items-center gap-4 w-full sm:w-auto">
                <a href="{{ route('register') }}"
                    class="w-full sm:w-auto px-6 py-3 bg-[#f97316] hover:bg-[#ea580c] text-white rounded-xl font-medium text-[15px] btn-glow">
                    Create your own Voice Assistant ->
                </a>
                {{--
                <a href="#pricing"
                    class="w-full sm:w-auto px-6 py-3 bg-[#1e293b]/80 border border-white/5 hover:bg-[#334155]/80 rounded-xl font-medium text-[15px] text-white transition-all backdrop-blur-md">
                    View pricing
                </a>
                --}}
            </div>
        </main>

        {{-- Trust / Value Strip --}}
        <section class="w-full px-4 mb-8">
            <div class="max-w-6xl mx-auto rounded-2xl border border-white/5 bg-black/30 backdrop-blur-md px-6 py-5">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6 text-center md:text-left">
                    <div class="flex flex-col gap-1">
                        <span class="text-[12px] uppercase tracking-[0.18em] text-[#f97316]">capture everything</span>
                        <p class="text-[14px] text-slate-300">Turn conversations into clean, trackable tickets without
                            manual note-taking.</p>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-[12px] uppercase tracking-[0.18em] text-[#f97316]">respond faster</span>
                        <p class="text-[14px] text-slate-300">Route issues to the right team instantly and reduce missed
                            follow-ups.</p>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-[12px] uppercase tracking-[0.18em] text-[#f97316]">stay accountable</span>
                        <p class="text-[14px] text-slate-300">Give your team structured records, timestamps, and clear
                            next steps on every call.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Features Section --}}
        <section class="w-full pt-16 pb-10 px-4">
            <div class="max-w-6xl mx-auto">
                <div class="text-center mb-14 py-12">
                    <h2 class="text-3xl font-bold tracking-tight mb-4 text-white lowercase">built for high-volume
                        support</h2>
                    <p class="text-[15px] text-slate-400 max-w-2xl mx-auto">
                        Everything you need to turn inbound support calls into a more reliable, more scalable workflow.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <div class="rounded-2xl border border-white/5 bg-black/40 backdrop-blur-md p-6">
                        <div class="w-10 h-2 rounded-xl"></div>
                        <h3 class="text-lg font-semibold text-white mb-2">Automatic ticket creation</h3>
                        <p class="text-[14px] leading-6 text-slate-300">
                            Convert every call into a structured support ticket with the important details already
                            organized.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/5 bg-black/40 backdrop-blur-md p-6">
                        <div class="w-10 h-2 rounded-xl"></div>
                        <h3 class="text-lg font-semibold text-white mb-2">Call summaries</h3>
                        <p class="text-[14px] leading-6 text-slate-300">
                            Reduce back-and-forth by generating concise summaries your team can review in seconds.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/5 bg-black/40 backdrop-blur-md p-6">
                        <div class="w-10 h-2 rounded-xl"></div>
                        <h3 class="text-lg font-semibold text-white mb-2">Smart routing</h3>
                        <p class="text-[14px] leading-6 text-slate-300">
                            Direct maintenance, support, or operational requests to the right person or department
                            immediately.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/5 bg-black/40 backdrop-blur-md p-6">
                        <div class="w-10 h-2 rounded-xl"></div>
                        <h3 class="text-lg font-semibold text-white mb-2">Status visibility</h3>
                        <p class="text-[14px] leading-6 text-slate-300">
                            Keep teams aligned with a clearer view of open issues, next actions, and ticket progress.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/5 bg-black/40 backdrop-blur-md p-6">
                        <div class="w-10 h-2 rounded-xl"></div>
                        <h3 class="text-lg font-semibold text-white mb-2">Analytics and trends</h3>
                        <p class="text-[14px] leading-6 text-slate-300">
                            Identify recurring issues, peak support periods, and service bottlenecks from one place.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/5 bg-black/40 backdrop-blur-md p-6">
                        <div class="w-10 h-2 rounded-xl"></div>
                        <h3 class="text-lg font-semibold text-white mb-2">Human-friendly workflows</h3>
                        <p class="text-[14px] leading-6 text-slate-300">
                            Give your staff structure without making the process feel heavier or more complicated.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- How It Works --}}
        <section class="w-full pt-14 pb-10 px-4">
            <div class="max-w-6xl mx-auto">
                <div class="text-center mb-14">
                    <h2 class="text-3xl font-bold tracking-tight mb-4 text-white lowercase">how it works</h2>
                    <p class="text-[15px] text-slate-400 max-w-2xl mx-auto">
                        A simple flow designed to reduce manual work and help your team close issues faster.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="rounded-2xl border border-white/5 bg-black/40 backdrop-blur-md p-8">
                        <div class="text-[#f97316] text-sm font-semibold mb-4">Step 1</div>
                        <h3 class="text-xl font-medium text-white mb-3">A customer calls</h3>
                        <p class="text-[14px] leading-6 text-slate-300">
                            Incoming support calls are captured and prepared for structured handling from the start.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/5 bg-black/40 backdrop-blur-md p-8">
                        <div class="text-[#f97316] text-sm font-semibold mb-4">Step 2</div>
                        <h3 class="text-xl font-medium text-white mb-3">A ticket is created</h3>
                        <p class="text-[14px] leading-6 text-slate-300">
                            Key details from the conversation are organized into a ticket your team can immediately act
                            on.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/5 bg-black/40 backdrop-blur-md p-8">
                        <div class="text-[#f97316] text-sm font-semibold mb-4">Step 3</div>
                        <h3 class="text-xl font-medium text-white mb-3">Your team closes the ticket</h3>
                        <p class="text-[14px] leading-6 text-slate-300">
                            Assigned teams review, update, and resolve requests with less delay and less internal
                            friction.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Use Cases / Integrations --}}
        <section class="w-full pt-14 pb-10 px-4">
            <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="rounded-2xl border border-white/5 bg-black/40 backdrop-blur-md p-8">
                    <h2 class="text-2xl font-bold tracking-tight mb-5 text-white lowercase">ideal for teams handling
                        repeat support volume</h2>
                    <ul class="space-y-4 text-[14px] text-slate-300">
                        <li class="flex items-start gap-3">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#f97316] mt-2"></span>
                            Property management teams handling maintenance or tenant support
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#f97316] mt-2"></span>
                            Customer support teams that rely heavily on inbound phone calls
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#f97316] mt-2"></span>
                            Operations teams that need clearer follow-up and handoff workflows
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#f97316] mt-2"></span>
                            Businesses looking to reduce manual intake and improve accountability
                        </li>
                    </ul>
                </div>

                <div class="rounded-2xl border border-white/5 bg-black/40 backdrop-blur-md p-8">
                    <h2 class="text-2xl font-bold tracking-tight mb-5 text-white lowercase">fits into your workflow</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div
                            class="rounded-xl border border-white/5 bg-white/5 px-4 py-4 text-sm text-slate-300 text-center">
                            phone systems
                        </div>
                        <div
                            class="rounded-xl border border-white/5 bg-white/5 px-4 py-4 text-sm text-slate-300 text-center">
                            email
                        </div>
                        <div
                            class="rounded-xl border border-white/5 bg-white/5 px-4 py-4 text-sm text-slate-300 text-center">
                            web chat
                        </div>
                        <div
                            class="rounded-xl border border-white/5 bg-white/5 px-4 py-4 text-sm text-slate-300 text-center">
                            support teams
                        </div>
                        <div
                            class="rounded-xl border border-white/5 bg-white/5 px-4 py-4 text-sm text-slate-300 text-center">
                            analytics
                        </div>
                        <div
                            class="rounded-xl border border-white/5 bg-white/5 px-4 py-4 text-sm text-slate-300 text-center">
                            internal ops
                        </div>
                    </div>
                </div>
            </div>
        </section>
        {{-- Pricing Section --}}
        {{--
        <section id="pricing" class="w-full pt-20 pb-32 px-4 scroll-mt-20">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold tracking-tight mb-4 text-white lowercase">features & pricing</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
                {{-- Startup Plan --}}
                <!-- <div
                    class="rounded-2xl p-8 flex flex-col border border-white/5 bg-black/40 backdrop-blur-md hover:border-white/10 transition-colors">
                    <h3 class="text-xl font-medium text-white mb-2">Startup</h3>
                    <div class="text-4xl font-bold text-white mb-6 mt-4">Custom</div>
                    <ul class="space-y-4 mb-8 flex-1 text-[14px] text-slate-300">
                        <li class="flex items-center gap-3">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#f97316]"></span>
                            AI Assistant Integration
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#f97316]"></span>
                            Email & Web Chat
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#f97316]"></span>
                            Standard Support
                        </li>
                    </ul>
                    <a href="{{ route('register') }}"
                        class="w-full py-3 px-4 border border-white/10 text-white font-medium rounded-xl text-center text-[14px] hover:bg-white/5 transition-colors">
                        Contact Sales
                    </a>
                </div> -->

                {{-- Pro Plan (Highlighted) --}}
                <!-- <div
                    class="rounded-2xl p-8 flex flex-col border border-[#f97316]/50 bg-black/60 backdrop-blur-md relative md:-mt-4 md:mb-4">
                    <div
                        class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 px-3 py-1 bg-[#f97316] rounded-full text-[10px] font-bold text-white tracking-widest uppercase shadow-lg shadow-[#f97316]/20">
                        Most Popular
                    </div>
                    <h3 class="text-xl font-medium text-white mb-2">Pro</h3>
                    <div class="text-4xl font-bold text-white mb-6 mt-4">Custom</div>
                    <ul class="space-y-4 mb-8 flex-1 text-[14px] text-slate-300">
                        <li class="flex items-center gap-3">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#f97316]"></span>
                            Everything in Startup
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#f97316]"></span>
                            Phone Integrations
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#f97316]"></span>
                            Advanced Analytics
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#f97316]"></span>
                            Priority Support
                        </li>
                    </ul>
                    <a href="{{ route('register') }}"
                        class="w-full py-3 px-4 bg-[#f97316] hover:bg-[#ea580c] text-white font-medium rounded-xl text-center text-[14px] btn-glow">
                        Contact Sales
                    </a>
                </div>
 -->
                {{-- Enterprise Plan --}}
                <!-- <div
                    class="rounded-2xl p-8 flex flex-col border border-white/5 bg-black/40 backdrop-blur-md hover:border-white/10 transition-colors">
                    <h3 class="text-xl font-medium text-white mb-2">Enterprise</h3>
                    <div class="text-4xl font-bold text-white mb-6 mt-4">Custom</div>
                    <ul class="space-y-4 mb-8 flex-1 text-[14px] text-slate-300">
                        <li class="flex items-center gap-3">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#f97316]"></span>
                            Everything in Pro
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#f97316]"></span>
                            Custom AI Models
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#f97316]"></span>
                            Dedicated Account Manager
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#f97316]"></span>
                            SLA Guidelines
                        </li>
                    </ul>
                    <a href="{{ route('register') }}"
                        class="w-full py-3 px-4 border border-white/10 text-white font-medium rounded-xl text-center text-[14px] hover:bg-white/5 transition-colors">
                        Contact Sales
                    </a>
                </div>
            </div>
        </section> -->


                {{-- FAQ Section --}}
                <section class="w-full pt-16 pb-10 px-4">
                    <div class="max-w-4xl mx-auto">
                        <div class="text-center mb-14">
                            <h2 class="text-3xl font-bold tracking-tight mb-4 text-white lowercase">frequently asked
                                questions
                            </h2>
                            <p class="text-[15px] text-slate-400">
                                A few common questions teams usually ask before getting started.
                            </p>
                        </div>

                        <div class="space-y-4">
                            <div class="rounded-2xl border border-white/5 bg-black/40 backdrop-blur-md p-6">
                                <h3 class="text-[16px] font-semibold text-white mb-2">Do I need to change my current
                                    support
                                    process?</h3>
                                <p class="text-[14px] leading-6 text-slate-300">
                                    No. ticketcloser is designed to fit into your existing workflow while making intake
                                    and
                                    follow-up more structured.
                                </p>
                            </div>

                            <div class="rounded-2xl border border-white/5 bg-black/40 backdrop-blur-md p-6">
                                <h3 class="text-[16px] font-semibold text-white mb-2">Can this work for phone-based
                                    support
                                    teams?</h3>
                                <p class="text-[14px] leading-6 text-slate-300">
                                    Yes. It is especially useful for teams that handle high volumes of inbound calls and
                                    need
                                    better ticket accuracy.
                                </p>
                            </div>

                            {{--
                            <div class="rounded-2xl border border-white/5 bg-black/40 backdrop-blur-md p-6">
                                <h3 class="text-[16px] font-semibold text-white mb-2">Is pricing fixed?</h3>
                                <p class="text-[14px] leading-6 text-slate-300">
                                    Pricing is custom so it can match the size of your team, your workflows, and the
                                    integrations you need.
                                </p>
                            </div>
                            --}}

                            <div class="rounded-2xl border border-white/5 bg-black/40 backdrop-blur-md p-6">
                                <h3 class="text-[16px] font-semibold text-white mb-2">Can larger teams get a tailored
                                    setup?
                                </h3>
                                <p class="text-[14px] leading-6 text-slate-300">
                                    Yes. Enterprise plans can be configured around more advanced routing, analytics, and
                                    support
                                    requirements.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>
                {{-- Final CTA --}}
                <section class="w-full pt-14 pb-24 px-4">
                    <div class="max-w-5xl mx-auto rounded-3xl bg-black/50 backdrop-blur-md px-8 py-12 text-center">
                        <h2 class="text-3xl md:text-4xl font-bold tracking-tight mb-4 text-white lowercase">
                            stop losing time on messy support intake
                        </h2>
                        <p class="text-[15px] md:text-[16px] text-slate-300 max-w-2xl mx-auto mb-8">
                            Give your team a faster way to capture, route, and close incoming requests with more
                            consistency.
                        </p>

                        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                            <a href="{{ route('register') }}"
                                class="w-full sm:w-auto px-6 py-3 bg-[#f97316] hover:bg-[#ea580c] text-white rounded-xl font-medium text-[15px] btn-glow">
                                Get started free
                            </a>
                            {{--
                            <a href="#pricing"
                                class="w-full sm:w-auto px-6 py-3 bg-[#1e293b]/80 border border-white/5 hover:bg-[#334155]/80 rounded-xl font-medium text-[15px] text-white transition-all backdrop-blur-md">
                                Explore pricing
                            </a>
                            --}}
                        </div>
                    </div>
                </section>

                {{-- Minimal Footer --}}
                <footer
                    class="w-full pt-20 pb-10 border-t border-white/5 text-center text-slate-500 text-[13px] bg-black/20 backdrop-blur-sm">
                    <p>&copy; {{ date('Y') }} ticketcloser. All rights reserved.</p>
                </footer>

            </div>

</body>

</html>