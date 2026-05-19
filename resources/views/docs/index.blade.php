@extends('layouts.marketing', [
    'metaTitle' => $metaTitle,
    'metaDescription' => $metaDescription,
    'metaCanonical' => $metaCanonical,
    'structuredData' => $structuredData,
    'navCurrent' => 'docs',
])

@section('content')
    <section class="px-5 pb-8 pt-16 lg:px-8 lg:pb-12 lg:pt-20">
        <div class="mx-auto max-w-7xl">
            <div class="tc-landing-panel tc-landing-card px-6 py-10 sm:px-8 sm:py-12">
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

    <section class="px-5 pb-10 pt-4 lg:px-8">
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
                    <article id="{{ $article['id'] }}" class="tc-landing-panel tc-landing-card px-6 py-8 sm:px-8">
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

                <section class="grid gap-5 xl:grid-cols-2">
                    <div class="tc-landing-panel tc-landing-card p-6 sm:p-8">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400">Feature deep dives</div>
                        <h2 class="mt-3 text-2xl font-semibold tracking-tight text-white">Read the product pages visitors search for first.</h2>
                        <div class="mt-6 space-y-4">
                            @foreach($featureCards as $card)
                                <a href="{{ $card['url'] }}" class="block rounded-[1.2rem] border border-white/10 bg-white/5 px-4 py-4 transition hover:border-white/18 hover:bg-white/8">
                                    <div class="text-sm font-semibold text-white">{{ $card['card_title'] }}</div>
                                    <p class="mt-2 text-sm leading-6 text-slate-300">{{ $card['card_summary'] }}</p>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <div class="tc-landing-panel tc-landing-card p-6 sm:p-8">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400">Industry playbooks</div>
                        <h2 class="mt-3 text-2xl font-semibold tracking-tight text-white">See how tickIt fits different inbound-call workflows.</h2>
                        <div class="mt-6 space-y-4">
                            @foreach($industryCards as $card)
                                <a href="{{ $card['url'] }}" class="block rounded-[1.2rem] border border-white/10 bg-white/5 px-4 py-4 transition hover:border-white/18 hover:bg-white/8">
                                    <div class="text-sm font-semibold text-white">{{ $card['card_title'] }}</div>
                                    <p class="mt-2 text-sm leading-6 text-slate-300">{{ $card['card_summary'] }}</p>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </section>

                <section class="tc-landing-panel tc-landing-card tc-landing-glow px-6 py-8 sm:px-8">
                    <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-orange-300/80">Need help</div>
                    <h2 class="mt-3 text-3xl font-semibold tracking-tight text-white">Want to learn by doing?</h2>
                    <p class="mt-4 max-w-2xl text-base leading-7 text-slate-300">
                        The fastest way to understand tickIt is still one real test call. Create a workspace, set up an assistant, connect a number, and watch the ticket appear.
                    </p>
                    <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('register') }}" class="tc-btn-glow !px-6 !py-3 text-base">Try for Free</a>
                        <a href="{{ route('features.index') }}" class="tc-btn-glass !px-6 !py-3 text-base">Browse features</a>
                    </div>
                </section>
            </div>
        </div>
    </section>
@endsection
