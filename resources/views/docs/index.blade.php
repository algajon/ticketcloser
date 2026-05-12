<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    @php
        $metaTitle = 'tickIt Docs | Learn how calls become tickets, contacts, and follow-up';
        $metaDescription = 'Simple tickIt docs for setting up your workspace, creating assistants, connecting your number, handling calls, and reviewing tickets, contacts, and meetings.';
        $metaCanonical = route('docs');
        $structuredData = [[
            '@context' => 'https://schema.org',
            '@type' => 'TechArticle',
            'headline' => 'tickIt Docs',
            'description' => $metaDescription,
            'url' => $metaCanonical,
            'author' => [
                '@type' => 'Organization',
                'name' => 'tickIt',
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'tickIt',
            ],
        ]];
        $articles = [
            [
                'id' => 'getting-started',
                'eyebrow' => 'Start here',
                'title' => 'Get from signup to first live call',
                'summary' => 'The quickest path to seeing tickIt work for real.',
                'steps' => [
                    'Create your account and verify your email.',
                    'Choose the type of calls your business gets most often.',
                    'Create your first assistant from the prefilled draft.',
                    'Connect a phone number so callers can reach the assistant.',
                    'Make a test call and review the ticket that appears in the app.',
                ],
            ],
            [
                'id' => 'workspace',
                'eyebrow' => 'Workspace setup',
                'title' => 'Set up your workspace the right way',
                'summary' => 'Your workspace is the home for your assistants, calls, tickets, contacts, and meetings.',
                'steps' => [
                    'Use the workspace name your team recognizes every day.',
                    'Choose the workflow that most closely matches your business, like maintenance, front desk, IT support, or customer support.',
                    'Let tickIt prefill your first assistant so you are not starting from a blank screen.',
                    'Keep one workspace per business or team that needs separate assistants and call history.',
                ],
            ],
            [
                'id' => 'assistant',
                'eyebrow' => 'Assistant setup',
                'title' => 'Create an assistant that sounds natural',
                'summary' => 'Assistants answer the phone, ask the right questions, and turn calls into tickets.',
                'steps' => [
                    'Pick the assistant name your team will recognize inside the app.',
                    'Choose a behavior preset that matches how you want the assistant to sound.',
                    'Set the first line the assistant says so your greeting feels on-brand.',
                    'Use the prompt draft as your starting point, then keep the instructions short, clear, and practical.',
                    'Save and sync after changes so the live phone assistant gets the newest version.',
                ],
            ],
            [
                'id' => 'number',
                'eyebrow' => 'Phone numbers',
                'title' => 'Connect your number and go live',
                'summary' => 'This is how callers actually reach your assistant.',
                'steps' => [
                    'Provision a number inside tickIt or forward your existing business number once the assistant is ready.',
                    'Keep one number per assistant when you want separate call flows.',
                    'Use the live status in the app to confirm the line is active before testing.',
                    'Place a real test call so you can hear the greeting and check the ticket flow.',
                ],
            ],
            [
                'id' => 'call-flow',
                'eyebrow' => 'What happens on a call',
                'title' => 'How tickIt handles a phone call',
                'summary' => 'The goal is to make the caller repeat less and give your team a cleaner record.',
                'steps' => [
                    'The assistant answers and greets the caller.',
                    'If the caller is already known, tickIt can recognize them and use saved context.',
                    'The assistant gathers the issue, urgency, and the details needed for follow-up.',
                    'After confirmation, tickIt creates the ticket and saves the call context.',
                    'If the workflow needs a meeting, tickIt can help move that into scheduling.',
                ],
            ],
            [
                'id' => 'after-call',
                'eyebrow' => 'After the call',
                'title' => 'Review tickets, contacts, and meetings',
                'summary' => 'Everything important from the call should be easy to find afterward.',
                'steps' => [
                    'Tickets show what happened, who called, and what needs to happen next.',
                    'Contacts keep repeat callers connected to their call history and past tickets.',
                    'Meetings and suggested follow-up stay attached to the relevant ticket.',
                    'Calls keep the recording and transcript together so your team can review what was said.',
                ],
            ],
            [
                'id' => 'improve',
                'eyebrow' => 'Improve results',
                'title' => 'Make the assistant better over time',
                'summary' => 'The best assistants get tighter with real call feedback.',
                'steps' => [
                    'Listen to a few real calls every week.',
                    'Shorten prompts when the assistant sounds too wordy.',
                    'Add clearer instructions when it misses key details.',
                    'Adjust the opening line and behavior preset if the tone feels off.',
                    'Keep using one test call after every important change.',
                ],
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

<body style="background:#020202;color:#fff;overflow-x:hidden" class="font-[Inter,system-ui,sans-serif] antialiased">
    <canvas id="three-canvas" style="position:fixed;top:0;left:0;width:100%;height:100%;z-index:0;pointer-events:none"></canvas>

    <div class="tc-landing-shell">
        <nav class="tc-landing-nav relative z-50 w-full border-b border-slate-200 bg-white px-6 py-5">
            <div class="relative mx-auto flex max-w-7xl items-center justify-between gap-6">
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <span class="text-[17px] font-bold tracking-tight text-slate-950">tickIt</span>
                </a>

                <div class="absolute left-1/2 hidden -translate-x-1/2 items-center gap-8 lg:flex">
                    <a href="{{ route('home') }}#platform" class="text-[13px] font-medium text-slate-600 transition-colors hover:text-slate-900">Platform</a>
                    <a href="{{ route('home') }}#workflow" class="text-[13px] font-medium text-slate-600 transition-colors hover:text-slate-900">Workflow</a>
                    <a href="{{ route('docs') }}" class="text-[13px] font-semibold text-slate-950">Docs</a>
                    <a href="{{ route('home') }}#operations" class="text-[13px] font-medium text-slate-600 transition-colors hover:text-slate-900">Operations</a>
                </div>

                <div class="flex items-center gap-6">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="text-[13px] font-medium text-slate-600 transition-colors hover:text-slate-900">dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-[13px] font-medium text-slate-600 transition-colors hover:text-slate-900">Sign in</a>
                        <a href="{{ route('register') }}" class="rounded-lg bg-[#f97316] px-4 py-2 text-[13px] font-medium text-white transition-colors hover:bg-[#ea580c]">
                            Try for Free
                        </a>
                    @endauth
                </div>
            </div>
        </nav>

        <main class="relative z-10">
            <section class="px-5 pb-8 pt-16 lg:px-8 lg:pb-12 lg:pt-20">
                <div class="mx-auto max-w-7xl">
                    <div class="tc-landing-panel tc-landing-card tc-landing-motion-stage px-6 py-10 sm:px-8 sm:py-12">
                        <div class="max-w-3xl">
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-orange-300/80">Docs</div>
                            <h1 class="mt-4 text-4xl font-semibold tracking-tight text-white sm:text-5xl">Learn tickIt without getting lost in technical details.</h1>
                            <p class="mt-5 text-base leading-7 text-slate-300">
                                These guides explain how tickIt works in plain language, from creating your first assistant to reviewing the ticket after a call.
                            </p>
                        </div>

                        <div class="mt-8 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            @foreach($articles as $article)
                                <a href="#{{ $article['id'] }}" class="rounded-[1.15rem] border border-white/10 bg-white/5 px-4 py-4 text-left transition hover:border-white/18 hover:bg-white/8">
                                    <div class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-400">{{ $article['eyebrow'] }}</div>
                                    <div class="mt-2 text-sm font-semibold text-white">{{ $article['title'] }}</div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            <section class="px-5 pb-24 pt-4 lg:px-8">
                <div class="mx-auto grid max-w-7xl gap-5 lg:grid-cols-[280px_minmax(0,1fr)]">
                    <aside class="hidden lg:block">
                        <div class="tc-landing-panel tc-landing-card sticky top-8 p-5">
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400">On this page</div>
                            <nav class="mt-5 space-y-2">
                                @foreach($articles as $article)
                                    <a href="#{{ $article['id'] }}" class="block rounded-xl px-3 py-2 text-sm text-slate-300 transition hover:bg-white/6 hover:text-white">
                                        {{ $article['title'] }}
                                    </a>
                                @endforeach
                            </nav>
                        </div>
                    </aside>

                    <div class="space-y-5">
                        @foreach($articles as $article)
                            <article id="{{ $article['id'] }}" class="tc-landing-panel tc-landing-card tc-landing-motion-stage px-6 py-8 sm:px-8">
                                <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400">{{ $article['eyebrow'] }}</div>
                                <h2 class="mt-3 text-3xl font-semibold tracking-tight text-white">{{ $article['title'] }}</h2>
                                <p class="mt-4 max-w-3xl text-base leading-7 text-slate-300">{{ $article['summary'] }}</p>

                                <div class="mt-8 grid gap-4 md:grid-cols-2">
                                    @foreach($article['steps'] as $index => $step)
                                        <div class="rounded-[1.35rem] border border-white/10 bg-white/5 p-5">
                                            <div class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-orange-300">Step {{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</div>
                                            <p class="mt-3 text-sm leading-7 text-slate-200">{{ $step }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </article>
                        @endforeach

                        <section class="tc-landing-panel tc-landing-card tc-landing-glow px-6 py-8 sm:px-8">
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-orange-300/80">Need help</div>
                            <h2 class="mt-3 text-3xl font-semibold tracking-tight text-white">Want to learn by doing?</h2>
                            <p class="mt-4 max-w-2xl text-base leading-7 text-slate-300">
                                The fastest way to understand tickIt is still one real test call. Create a workspace, set up an assistant, connect a number, and watch the ticket appear.
                            </p>
                            <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                                <a href="{{ route('register') }}" class="tc-btn-glow !px-6 !py-3 text-base">Try for Free</a>
                                <a href="{{ route('home') }}#workflow" class="tc-btn-glass !px-6 !py-3 text-base">Back to overview</a>
                            </div>
                        </section>
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>

</html>
