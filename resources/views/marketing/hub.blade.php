@extends('layouts.marketing', [
    'metaTitle' => $metaTitle,
    'metaDescription' => $metaDescription,
    'metaCanonical' => $metaCanonical,
    'structuredData' => $structuredData,
    'navCurrent' => $navCurrent,
])

@section('content')
    <section class="px-5 py-16 lg:px-8 lg:py-20">
        <div class="mx-auto max-w-7xl">
            <div class="tc-landing-panel tc-landing-card px-6 py-10 sm:px-8 sm:py-12">
                <div class="max-w-4xl">
                    <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-orange-300/80">{{ $eyebrow }}</div>
                    <h1 class="mt-4 text-4xl font-semibold tracking-tight text-white sm:text-5xl">{{ $title }}</h1>
                    <p class="mt-5 max-w-3xl text-base leading-8 text-slate-300">{{ $description }}</p>
                </div>

                <div class="mt-8 grid gap-5 lg:grid-cols-2">
                    @foreach($cards as $card)
                        <a href="{{ $card['url'] }}" class="rounded-[1.45rem] border border-white/10 bg-white/5 p-6 transition hover:-translate-y-0.5 hover:border-white/18 hover:bg-white/8">
                            <div class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-400">{{ $card['label'] }}</div>
                            <h2 class="mt-4 text-2xl font-semibold text-white">{{ $card['card_title'] }}</h2>
                            <p class="mt-4 text-sm leading-7 text-slate-300">{{ $card['card_summary'] }}</p>
                            <div class="mt-5 flex flex-wrap gap-2">
                                @foreach(array_slice($card['highlights'], 0, 4) as $highlight)
                                    <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-medium text-slate-200">{{ $highlight }}</span>
                                @endforeach
                            </div>
                        </a>
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
                    <h2 class="mt-4 text-3xl font-semibold tracking-tight text-white sm:text-4xl">Short answers for high-intent questions.</h2>
                </div>

                <div class="mt-8 grid gap-4 lg:grid-cols-3">
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
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-orange-300/80">Next step</div>
                        <h2 class="mt-4 text-3xl font-semibold tracking-tight text-white sm:text-4xl">{{ $ctaTitle }}</h2>
                        <p class="mt-4 text-base leading-7 text-slate-300">{{ $ctaCopy }}</p>
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
