@php
    $routeName = Route::currentRouteName() ?? '';
    $authWidth = trim($__env->yieldContent('auth_width')) ?: 'max-w-md';
    $guestLayout = trim($__env->yieldContent('guest_layout')) ?: 'split';
    $guestEyebrow = trim($__env->yieldContent('guest_eyebrow')) ?: 'AI phone support';
    $guestTitle = trim($__env->yieldContent('guest_title')) ?: 'Answer calls. Create tickets. Keep work moving.';
    $guestCopy = trim($__env->yieldContent('guest_copy')) ?: 'tickIt answers calls, captures the details, creates the ticket, and keeps follow-up in one place.';
    $metaTitle = trim($__env->yieldContent('title')) ?: 'tickIt';
    $metaDescription = trim($__env->yieldContent('meta_description')) ?: $guestCopy;
    $metaRobots = trim($__env->yieldContent('robots')) ?: (str_starts_with($routeName, 'login') || str_starts_with($routeName, 'register') || str_starts_with($routeName, 'password.') || str_starts_with($routeName, 'verification.') ? 'noindex,follow' : 'index,follow');
    $metaCanonical = trim($__env->yieldContent('canonical')) ?: url()->current();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.seo.meta', [
        'title' => $metaTitle,
        'description' => $metaDescription,
        'canonical' => $metaCanonical,
        'robots' => $metaRobots,
        'structuredData' => [],
    ])
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    @include('partials.analytics.google-tag')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
    @stack('structured_data')
</head>

<body style="background:#020202;color:#fff;overflow-x:hidden;" class="font-[Inter,system-ui,sans-serif] antialiased">
    <canvas id="three-canvas" style="position:fixed;top:0;left:0;width:100%;height:100%;z-index:0;pointer-events:none"></canvas>

    <div class="tc-auth-shell">
        <div class="fixed left-0 top-0 z-50 w-full p-6">
            <div class="mx-auto flex max-w-7xl items-center justify-between">
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <span
                        class="text-lg font-bold tracking-tight text-white transition-opacity hover:opacity-80">tickIt</span>
                </a>
                <div class="flex items-center gap-6">
                    @if($routeName === 'register')
                        <a href="{{ route('login') }}"
                            class="text-sm font-medium text-slate-300 transition-colors hover:text-white">Sign in</a>
                    @elseif($routeName === 'login')
                        <a href="{{ route('register') }}"
                            class="tc-btn-primary !rounded-[0.95rem] !px-4 !py-2 text-sm">Try for Free</a>
                    @endif
                </div>
            </div>
        </div>

        <main class="{{ $guestLayout === 'centered' ? 'tc-auth-grid-centered' : 'tc-auth-grid' }}">
            @if($guestLayout !== 'centered')
                <section class="tc-auth-aside">
                    <div>
                        <div class="tc-label-eyebrow text-orange-300/80">{{ $guestEyebrow }}</div>
                        <h1 class="mt-6 max-w-xl text-4xl font-semibold leading-tight tracking-tight text-white">
                            {{ $guestTitle }}
                        </h1>
                        <p class="mt-5 max-w-2xl text-base leading-7 text-slate-300">
                            {{ $guestCopy }}
                        </p>
                    </div>

                    @hasSection('guest_aside')
                        <div>
                            @yield('guest_aside')
                        </div>
                    @else
                        <div class="tc-landing-panel tc-landing-glow p-7">
                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <div class="tc-label-eyebrow-tight text-slate-400">Capture</div>
                                    <p class="mt-2 text-sm leading-6 text-slate-200">Turn calls into clean records, transcripts, and ticket-ready intake.</p>
                                </div>
                                <div>
                                    <div class="tc-label-eyebrow-tight text-slate-400">Coordinate</div>
                                    <p class="mt-2 text-sm leading-6 text-slate-200">Route follow-up, scheduling, and ownership from the same control layer.</p>
                                </div>
                            </div>

                            <ul class="tc-auth-list mt-8">
                                <li>Review calls, tickets, and assistant coverage from one workspace.</li>
                                <li>Keep intake, timestamps, and next steps visible to the team.</li>
                                <li>Move from setup to live traffic without a generic helpdesk shell.</li>
                            </ul>
                        </div>
                    @endif
                </section>
            @endif

            <section class="relative z-10 w-full">
                <div class="mx-auto w-full {{ $authWidth }}">
                    <div class="tc-auth-card {{ $guestLayout === 'centered' ? 'tc-auth-card-centered' : '' }}">
                        @yield('content')
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>

</html>
