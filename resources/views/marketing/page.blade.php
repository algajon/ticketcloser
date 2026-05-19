@extends('layouts.marketing', [
    'metaTitle' => $metaTitle,
    'metaDescription' => $metaDescription,
    'metaCanonical' => $metaCanonical,
    'structuredData' => $structuredData,
    'navCurrent' => $navCurrent,
])

@section('content')
    <section class="px-5 py-14 lg:px-8 lg:py-18">
        <div class="mx-auto max-w-7xl">
            <nav class="mb-6 flex flex-wrap items-center gap-2 text-sm text-slate-400">
                @foreach($breadcrumbs as $crumb)
                    <a href="{{ $crumb['url'] }}" class="transition hover:text-white">{{ $crumb['label'] }}</a>
                    @if(! $loop->last)
                        <span>/</span>
                    @endif
                @endforeach
            </nav>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1.02fr)_minmax(0,0.98fr)]">
                <div class="tc-landing-panel tc-landing-card p-8 sm:p-10">
                    <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-orange-300/80">{{ $page['label'] }}</div>
                    <h1 class="mt-4 text-4xl font-semibold tracking-tight text-white sm:text-5xl">{{ $page['hero_title'] }}</h1>
                    <p class="mt-5 max-w-3xl text-base leading-8 text-slate-300">{{ $page['hero_description'] }}</p>

                    <div class="mt-6 flex flex-wrap gap-2">
                        @foreach($page['highlights'] as $highlight)
                            <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-medium text-slate-200">{{ $highlight }}</span>
                        @endforeach
                    </div>

                    <div class="mt-8 rounded-[1.35rem] border border-white/10 bg-white/5 p-6">
                        <div class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-400">Direct answer</div>
                        <p class="mt-3 text-base leading-8 text-slate-200">{{ $page['direct_answer'] }}</p>
                    </div>
                </div>

                <div class="grid gap-5">
                    @foreach($page['benefits'] as $benefit)
                        <div class="rounded-[1.4rem] border border-white/10 bg-white/5 p-6">
                            <h2 class="text-xl font-semibold text-white">{{ $benefit['title'] }}</h2>
                            <p class="mt-3 text-sm leading-7 text-slate-300">{{ $benefit['body'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="px-5 py-4 lg:px-8 lg:py-8">
        <div class="mx-auto max-w-7xl">
            <div class="grid gap-5 lg:grid-cols-3">
                @foreach($page['sections'] as $section)
                    <article class="tc-landing-panel tc-landing-card p-6">
                        <h2 class="text-2xl font-semibold text-white">{{ $section['title'] }}</h2>
                        <p class="mt-4 text-sm leading-7 text-slate-300">{{ $section['body'] }}</p>

                        <ul class="mt-5 space-y-3 text-sm leading-6 text-slate-200">
                            @foreach($section['bullets'] as $bullet)
                                <li class="flex items-start gap-3">
                                    <span class="mt-2 h-2 w-2 rounded-full bg-orange-400"></span>
                                    <span>{{ $bullet }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="px-5 py-4 lg:px-8 lg:py-8">
        <div class="mx-auto max-w-7xl">
            <div class="tc-landing-panel tc-landing-card p-8">
                <div class="max-w-3xl">
                    <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400">Best fit</div>
                    <h2 class="mt-4 text-3xl font-semibold tracking-tight text-white sm:text-4xl">Who usually gets the most value from this.</h2>
                </div>

                <div class="mt-8 grid gap-4 lg:grid-cols-3">
                    @foreach($page['fit_points'] as $point)
                        <div class="rounded-[1.35rem] border border-white/10 bg-white/5 p-5">
                            <p class="text-sm leading-7 text-slate-200">{{ $point }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="px-5 py-4 lg:px-8 lg:py-8">
        <div class="mx-auto max-w-7xl">
            <div class="tc-landing-panel tc-landing-card p-8">
                <div class="max-w-3xl">
                    <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400">FAQ</div>
                    <h2 class="mt-4 text-3xl font-semibold tracking-tight text-white sm:text-4xl">Questions teams ask before they try this workflow.</h2>
                </div>

                <div class="mt-8 grid gap-4 lg:grid-cols-2">
                    @foreach($page['faq_items'] as $faq)
                        <div class="rounded-[1.35rem] border border-white/10 bg-white/5 p-5">
                            <h3 class="text-lg font-semibold text-white">{{ $faq['question'] }}</h3>
                            <p class="mt-3 text-sm leading-7 text-slate-300">{{ $faq['answer'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="px-5 py-4 lg:px-8 lg:py-8">
        <div class="mx-auto grid max-w-7xl gap-5 lg:grid-cols-2">
            <div class="tc-landing-panel tc-landing-card p-6 sm:p-8">
                <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400">Related features</div>
                <div class="mt-6 space-y-4">
                    @foreach($relatedFeatures as $card)
                        <a href="{{ $card['url'] }}" class="block rounded-[1.2rem] border border-white/10 bg-white/5 px-4 py-4 transition hover:border-white/18 hover:bg-white/8">
                            <div class="text-sm font-semibold text-white">{{ $card['card_title'] }}</div>
                            <p class="mt-2 text-sm leading-6 text-slate-300">{{ $card['card_summary'] }}</p>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="tc-landing-panel tc-landing-card p-6 sm:p-8">
                <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400">Related industries</div>
                <div class="mt-6 space-y-4">
                    @foreach($relatedIndustries as $card)
                        <a href="{{ $card['url'] }}" class="block rounded-[1.2rem] border border-white/10 bg-white/5 px-4 py-4 transition hover:border-white/18 hover:bg-white/8">
                            <div class="text-sm font-semibold text-white">{{ $card['card_title'] }}</div>
                            <p class="mt-2 text-sm leading-6 text-slate-300">{{ $card['card_summary'] }}</p>
                        </a>
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
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-orange-300/80">Next step</div>
                        <h2 class="mt-4 text-3xl font-semibold tracking-tight text-white sm:text-4xl">See this workflow on one real call.</h2>
                        <p class="mt-4 text-base leading-7 text-slate-300">
                            Create a workspace, pick the closest workflow, connect a number, and watch the ticket appear with the transcript and follow-up context attached.
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
