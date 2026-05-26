<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

@php
    use App\Support\MarketingPageCatalog;

    $navCurrent = $navCurrent ?? 'home';
    $metaTitle = $metaTitle ?? 'tickIt';
    $metaDescription = $metaDescription ?? 'tickIt helps businesses automate phone calls, create tickets, capture call details, and keep follow-up moving.';
    $metaCanonical = $metaCanonical ?? url()->current();
    $structuredData = $structuredData ?? [];
    $footerFeatures = array_slice(MarketingPageCatalog::features(), 0, 4);
    $footerIndustries = array_slice(MarketingPageCatalog::industries(), 0, 4);
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
    <canvas id="three-canvas" style="position:fixed;top:0;left:0;width:100%;height:100%;z-index:0;pointer-events:none"></canvas>

    <div class="tc-landing-shell">
        <nav class="tc-landing-nav relative z-50 w-full border-b border-slate-200 bg-white px-6 py-5">
            <div class="relative mx-auto flex max-w-7xl items-center justify-between gap-6">
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <span class="text-lg font-bold tracking-tight text-slate-950">tickIt</span>
                </a>

                <div class="absolute left-1/2 hidden -translate-x-1/2 items-center gap-8 lg:flex">
                    <a href="{{ route('features.index') }}"
                        class="text-sm {{ $navCurrent === 'features' ? 'font-semibold text-slate-950' : 'font-medium text-slate-600 hover:text-slate-900' }} transition-colors">
                        Features
                    </a>
                    <a href="{{ route('industries.index') }}"
                        class="text-sm {{ $navCurrent === 'industries' ? 'font-semibold text-slate-950' : 'font-medium text-slate-600 hover:text-slate-900' }} transition-colors">
                        Industries
                    </a>
                    <a href="{{ route('docs') }}"
                        class="text-sm {{ $navCurrent === 'docs' ? 'font-semibold text-slate-950' : 'font-medium text-slate-600 hover:text-slate-900' }} transition-colors">
                        Docs
                    </a>
                </div>

                <div class="flex items-center gap-6">
                    @auth
                        <a href="{{ url('/dashboard') }}"
                            class="text-sm font-medium text-slate-600 transition-colors hover:text-slate-900">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}"
                            class="text-sm font-medium text-slate-600 transition-colors hover:text-slate-900">Sign
                            in</a>
                        <a href="{{ route('register') }}"
                            class="tc-btn-primary !px-4 !py-2.5 text-sm">
                            Try for Free
                        </a>
                    @endauth
                </div>
            </div>
        </nav>

        <main class="relative z-10">
            @yield('content')
        </main>

        <footer class="relative z-10 px-5 pb-20 pt-6 lg:px-8">
            <div class="mx-auto max-w-7xl border-t border-white/10 pt-8">
                <div class="grid gap-8 lg:grid-cols-[minmax(0,1.2fr)_repeat(3,minmax(0,0.8fr))]">
                    <div>
                        <div class="text-lg font-semibold text-white">tickIt</div>
                        <p class="mt-3 max-w-md text-sm leading-7 text-slate-300">
                            AI phone answering that turns business calls into tickets, transcripts, and cleaner follow-up for teams that depend on inbound calls.
                        </p>
                        <div class="mt-5 flex flex-wrap gap-3">
                            <a href="{{ route('register') }}" class="tc-btn-glow !px-5 !py-2.5 text-sm">Try for Free</a>
                            <a href="{{ route('docs') }}" class="tc-btn-glass !px-5 !py-2.5 text-sm">Read Docs</a>
                        </div>
                    </div>

                    <div>
                        <div class="tc-label-eyebrow text-slate-400">Features</div>
                        <div class="mt-4 space-y-3">
                            <a href="{{ route('features.index') }}" class="block text-sm text-slate-300 transition hover:text-white">All features</a>
                            @foreach($footerFeatures as $item)
                                <a href="{{ route('features.show', ['page' => $item['slug']]) }}" class="block text-sm text-slate-300 transition hover:text-white">
                                    {{ $item['nav_label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <div class="tc-label-eyebrow text-slate-400">Industries</div>
                        <div class="mt-4 space-y-3">
                            <a href="{{ route('industries.index') }}" class="block text-sm text-slate-300 transition hover:text-white">All industries</a>
                            @foreach($footerIndustries as $item)
                                <a href="{{ route('industries.show', ['page' => $item['slug']]) }}" class="block text-sm text-slate-300 transition hover:text-white">
                                    {{ $item['nav_label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <div class="tc-label-eyebrow text-slate-400">Resources</div>
                        <div class="mt-4 space-y-3">
                            <a href="{{ route('home') }}" class="block text-sm text-slate-300 transition hover:text-white">Homepage</a>
                            <a href="{{ route('docs') }}" class="block text-sm text-slate-300 transition hover:text-white">Docs</a>
                            <a href="{{ route('llms') }}" class="block text-sm text-slate-300 transition hover:text-white">llms.txt</a>
                            <a href="{{ route('terms') }}" class="block text-sm text-slate-300 transition hover:text-white">Terms</a>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex flex-col gap-3 border-t border-white/10 pt-6 text-sm text-slate-400 sm:flex-row sm:items-center sm:justify-between">
                    <div>tickIt helps teams answer calls, create tickets, and keep follow-up moving.</div>
                    <div>&copy; {{ date('Y') }} tickIt</div>
                </div>
            </div>
        </footer>
    </div>
</body>

</html>
