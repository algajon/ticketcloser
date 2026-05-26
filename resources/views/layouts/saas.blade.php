@php
    $routeName = Route::currentRouteName() ?? '';
    $authUser = auth()->user();
    $workspace = $workspace ?? ($authUser?->currentWorkspace());
    $decodeHeaderText = static fn ($value) => html_entity_decode(trim((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $pageTitle = $decodeHeaderText($__env->yieldContent('header')) ?: 'Workspace';
    $pageEyebrow = $decodeHeaderText($__env->yieldContent('header_eyebrow')) ?: ($workspace ? 'Workspace' : 'Overview');
    $pageDescription = $decodeHeaderText($__env->yieldContent('header_description'));
    $pageMeta = trim($__env->yieldContent('header_meta'));
    $mobilePageTitle = match (true) {
        str_starts_with($routeName, 'app.dashboard') => 'Dashboard',
        str_starts_with($routeName, 'app.tickets.index') => 'Tickets',
        str_starts_with($routeName, 'app.tickets.show') => 'Ticket',
        str_starts_with($routeName, 'app.calls.index') => 'Calls',
        str_starts_with($routeName, 'app.calls.show') => 'Call',
        str_starts_with($routeName, 'app.calls.analytics') => 'Analytics',
        str_starts_with($routeName, 'app.assistant.') => 'Assistant',
        str_starts_with($routeName, 'app.phone_numbers.') => 'Numbers',
        str_starts_with($routeName, 'app.contacts.') => 'Contacts',
        str_starts_with($routeName, 'app.calendar.') => 'Calendar',
        str_starts_with($routeName, 'app.integrations.') => 'Apps',
        str_starts_with($routeName, 'app.workspaces.') => 'Workspaces',
        str_starts_with($routeName, 'app.billing.') => 'Billing',
        str_starts_with($routeName, 'app.settings') => 'Settings',
        default => $pageTitle,
    };

    $descriptionMap = [
        'app.dashboard' => 'See open work, recent calls, and what still needs setup.',
        'app.tickets.index' => 'Search, filter, and update your tickets.',
        'app.calls.index' => 'See recordings, transcripts, and call history.',
        'app.calls.analytics' => 'See how many calls came in and what they turned into.',
        'app.assistant.edit' => 'Choose the voice, prompt, and call rules.',
        'app.phone_numbers.index' => 'Connect a number to an assistant.',
        'app.calendar.index' => 'See upcoming meetings and booking requests.',
        'app.billing.index' => 'See your plan, minutes, and invoices.',
        'app.settings' => 'Manage your account and workspace.',
        'app.integrations.index' => 'Connect the tools your team uses.',
        'app.workspaces.index' => 'Switch workspaces and manage each one.',
    ];

    if ($pageDescription === '') {
        $pageDescription = $descriptionMap[$routeName] ?? 'Manage your workspace.';
    }

    $workspaceRouteParams = $workspace ? ['workspace' => $workspace] : [];
    $navUpdateCounts = [
        'cases' => 0,
        'calls' => 0,
        'calendar' => 0,
    ];

    if ($workspace) {
        $navUpdateCounts['cases'] = \App\Models\SupportCase::query()
            ->where('workspace_id', $workspace->id)
            ->where('status', \App\Models\SupportCase::STATUS_NEW)
            ->count();

        $navUpdateCounts['calls'] = \App\Models\CallEvent::query()
            ->where('workspace_id', $workspace->id)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        $navUpdateCounts['calendar'] =
            \App\Models\SuggestedEvent::query()
                ->where('workspace_id', $workspace->id)
                ->where('status', 'pending')
                ->count()
            +
            \App\Models\CalendarEvent::query()
                ->where('workspace_id', $workspace->id)
                ->where('status', 'created')
                ->where('starts_at', '>=', now())
                ->where('created_at', '>=', now()->subDay())
                ->count();
    }

    $navGroups = [
        [
            'label' => 'Overview',
            'items' => [
                ['name' => 'Dashboard', 'route' => 'app.dashboard', 'match' => 'app.dashboard', 'params' => []],
                ['name' => 'Tickets', 'route' => 'app.tickets.index', 'match' => 'app.tickets.', 'params' => [], 'badgeKey' => 'cases'],
                ['name' => 'Calls', 'route' => 'app.calls.index', 'match' => ['app.calls.index', 'app.calls.show'], 'params' => $workspace ? [$workspace] : [], 'workspace' => true, 'badgeKey' => 'calls'],
                ['name' => 'Contacts', 'route' => 'app.contacts.index', 'match' => 'app.contacts.', 'params' => $workspace ? [$workspace] : [], 'workspace' => true],
                ['name' => 'Calendar', 'route' => 'app.calendar.index', 'match' => 'app.calendar.', 'params' => [], 'badgeKey' => 'calendar'],
                ['name' => 'Analytics', 'route' => 'app.calls.analytics', 'match' => ['app.calls.analytics'], 'params' => $workspace ? [$workspace] : [], 'workspace' => true],
            ],
        ],
        [
            'label' => 'Configuration',
            'items' => [
                ['name' => 'Assistants', 'route' => 'app.assistant.edit', 'match' => 'app.assistant.', 'params' => $workspace ? [$workspace] : [], 'workspace' => true],
                ['name' => 'Phone Numbers', 'route' => 'app.phone_numbers.index', 'match' => 'app.phone_numbers.', 'params' => $workspace ? [$workspace] : [], 'workspace' => true],
                ['name' => 'Integrations', 'route' => 'app.integrations.index', 'match' => 'app.integrations.', 'params' => $workspace ? [$workspace] : [], 'workspace' => true],
            ],
        ],
        [
            'label' => 'Workspace',
            'items' => [
                ['name' => 'Workspaces', 'route' => 'app.workspaces.index', 'match' => 'app.workspaces.', 'params' => []],
                ['name' => 'Billing', 'route' => 'app.billing.index', 'match' => 'app.billing.', 'params' => []],
                ['name' => 'Settings', 'route' => 'app.settings', 'match' => 'app.settings', 'params' => []],
            ],
        ],
    ];

    $adminNav = [];
    if ($authUser?->isAdmin()) {
        $adminNav = [
            ['name' => 'Admin Billing', 'route' => 'admin.billing.index', 'match' => 'admin.billing.', 'params' => []],
            ['name' => 'Admin Presets', 'route' => 'admin.presets.index', 'match' => 'admin.presets.', 'params' => []],
        ];
    }

    $workspacePlan = $workspace?->planLabel();
    $assistantReviewPrompt = session('assistant_review_prompt');
    $reviewWidgetConfig = $workspace && $assistantReviewPrompt ? [
        'enabled' => true,
        'url' => route('app.feedback.store', $workspace),
        'csrf' => csrf_token(),
        'assistantId' => $assistantReviewPrompt['assistant_id'] ?? null,
        'assistantName' => $assistantReviewPrompt['assistant_name'] ?? 'your assistant',
        'pageTitle' => $pageTitle,
    ] : null;
    $helperWidgetConfig = $workspace ? [
        'url' => route('app.helper.chat', $workspace),
        'csrf' => csrf_token(),
        'page' => [
            'title' => $pageTitle,
            'description' => $pageDescription,
            'path' => request()->path(),
        ],
    ] : null;
    $browserNotificationConfig = $workspace ? [
        'workspaceId' => $workspace->id,
        'counts' => $navUpdateCounts,
        'labels' => [
            'cases' => 'ticket',
            'calls' => 'call',
            'calendar' => 'meeting',
        ],
        'links' => [
            'cases' => route('app.tickets.index'),
            'calls' => route('app.calls.index', $workspaceRouteParams),
            'calendar' => route('app.calendar.index'),
        ],
    ] : null;
    $routeOrNull = static function (string $name, array $params = []) {
        try {
            return route($name, $params);
        } catch (\Throwable) {
            return null;
        }
    };
    $workspaceBreadcrumbUrl = $workspace ? $routeOrNull('app.dashboard') : $routeOrNull('home');
    $sectionBreadcrumbUrl = match (true) {
        str_starts_with($routeName, 'app.dashboard') => $routeOrNull('app.dashboard'),
        str_starts_with($routeName, 'app.tickets.') => $routeOrNull('app.tickets.index'),
        str_starts_with($routeName, 'app.calls.analytics') => $workspace ? $routeOrNull('app.calls.analytics', $workspaceRouteParams) : null,
        str_starts_with($routeName, 'app.calls.') => $workspace ? $routeOrNull('app.calls.index', $workspaceRouteParams) : null,
        str_starts_with($routeName, 'app.calendar.') => $routeOrNull('app.calendar.index'),
        str_starts_with($routeName, 'app.assistant.') => $workspace ? $routeOrNull('app.assistant.edit', $workspaceRouteParams) : null,
        str_starts_with($routeName, 'app.phone_numbers.') => $workspace ? $routeOrNull('app.phone_numbers.index', $workspaceRouteParams) : null,
        str_starts_with($routeName, 'app.contacts.') => $workspace ? $routeOrNull('app.contacts.index', $workspaceRouteParams) : null,
        str_starts_with($routeName, 'app.integrations.') => $workspace ? $routeOrNull('app.integrations.index', $workspaceRouteParams) : null,
        str_starts_with($routeName, 'app.workspaces.') => $routeOrNull('app.workspaces.index'),
        str_starts_with($routeName, 'app.billing.') => $routeOrNull('app.billing.index'),
        str_starts_with($routeName, 'app.settings.') || $routeName === 'app.settings' => $routeOrNull('app.settings'),
        str_starts_with($routeName, 'app.prompt-writer.') => $routeOrNull('app.prompt-writer.index'),
        str_starts_with($routeName, 'admin.billing.') => $routeOrNull('admin.billing.index'),
        str_starts_with($routeName, 'admin.presets.') => $routeOrNull('admin.presets.index'),
        default => null,
    };
@endphp
<!doctype html>
<html lang="en" class="h-full" data-app-accent="orange">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.seo.meta', [
        'title' => trim($__env->yieldContent('title')) ?: ('tickIt - ' . $pageTitle),
        'description' => $pageDescription ?: 'Manage your workspace in tickIt.',
        'canonical' => url()->current(),
        'robots' => 'noindex,nofollow',
        'structuredData' => [],
    ])
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap" rel="stylesheet" />
    @include('partials.analytics.google-tag')
    <script>
        (() => {
            const allowed = ['orange', 'blue', 'green', 'purple'];

            try {
                const stored = window.localStorage.getItem('tickit-app-accent');
                document.documentElement.dataset.appAccent = allowed.includes(stored) ? stored : 'orange';
            } catch (_error) {
                document.documentElement.dataset.appAccent = 'orange';
            }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>

    <body class="tc-shell-body bg-slate-100 font-[Inter,system-ui,sans-serif]"
    :data-app-accent="currentAccent"
    x-data="{
        sidebarOpen: false,
        userMenuOpen: false,
        currentAccent: document.documentElement.dataset.appAccent || 'orange',
        headerHidden: false,
        headerTicking: false,
        initAccent() {
            const allowed = ['orange', 'blue', 'green', 'purple'];
            const stored = localStorage.getItem('tickit-app-accent');
            this.currentAccent = allowed.includes(stored) ? stored : 'orange';
            document.documentElement.dataset.appAccent = this.currentAccent;
            document.body.dataset.appAccent = this.currentAccent;
        },
        applyAccent(accent) {
            const allowed = ['orange', 'blue', 'green', 'purple'];
            if (!allowed.includes(accent)) {
                return;
            }

            this.currentAccent = accent;
            document.documentElement.dataset.appAccent = accent;
            document.body.dataset.appAccent = accent;
            localStorage.setItem('tickit-app-accent', accent);
        },
        syncMobileScrollLock() {
            const shouldLock = this.sidebarOpen && window.innerWidth < 768;
            document.documentElement.classList.toggle('overflow-hidden', shouldLock);
            document.body.classList.toggle('overflow-hidden', shouldLock);
        },
        syncHeader() {
            this.headerHidden = (window.scrollY || document.documentElement.scrollTop || document.body.scrollTop || 0) > 24;
        },
        handleScroll() {
            if (this.headerTicking) return;
            this.headerTicking = true;
            requestAnimationFrame(() => {
                this.syncHeader();
                this.headerTicking = false;
            });
        },
    }"
    x-init="initAccent(); syncHeader(); syncMobileScrollLock(); $watch('sidebarOpen', () => syncMobileScrollLock()); window.addEventListener('resize', () => syncMobileScrollLock(), { passive: true }); window.addEventListener('scroll', () => handleScroll(), { passive: true });">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[70] focus:rounded-xl focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-slate-900">
        Skip to content
    </a>

    <div class="fixed inset-0 z-40 bg-slate-950/45 backdrop-blur-sm md:hidden" x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"></div>

    <div class="flex min-h-screen">
        <aside
            class="tc-shell-sidebar fixed inset-y-0 left-0 z-50 flex w-[17.5rem] -translate-x-full flex-col overflow-y-auto border-r px-5 py-5 shadow-[0_28px_80px_-38px_rgba(15,23,42,0.42)] transition-transform duration-200 md:static md:translate-x-0 md:shadow-none"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'">
            <div class="flex items-center justify-end gap-3">
                <button type="button" class="rounded-xl px-3 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 hover:bg-slate-100 hover:text-slate-700 md:hidden" @click="sidebarOpen = false" aria-label="Close navigation">
                    Close
                </button>
            </div>

            @if($workspace)
                <div class="tc-shell-workspace-card mt-3 rounded-[1.4rem] border p-4 shadow-[0_18px_45px_-32px_rgba(15,23,42,0.34)] md:mt-0">
                    <div class="flex items-start gap-3">
                        @if($workspace->logoUrl())
                            <div class="tc-shell-workspace-logo-frame shrink-0">
                                <img src="{{ $workspace->logoUrl() }}" alt="{{ $workspace->name }} logo" class="tc-shell-workspace-logo-image">
                            </div>
                        @endif

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-950">{{ $workspace->name }}</p>
                            <p class="mt-1 truncate text-xs text-slate-500">{{ $workspace->slug }}</p>
                        </div>
                    </div>

                </div>
            @endif

            <nav class="mt-6 pr-1" aria-label="Primary">
                @foreach($navGroups as $group)
                    <div class="{{ !$loop->first ? 'mt-6 border-t tc-shell-divider pt-6' : '' }}">
                        <div class="mb-3 px-2 text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-slate-400">
                            {{ $group['label'] }}
                        </div>

                        <div class="space-y-1.5">
                            @foreach($group['items'] as $item)
                                @if(($item['workspace'] ?? false) && !$workspace)
                                    @continue
                                @endif

                                @php
                                    $matchPatterns = (array) $item['match'];
                                    $active = collect($matchPatterns)->contains(fn ($pattern) => str_starts_with($routeName, $pattern));
                                    try {
                                        $url = route($item['route'], $item['params']);
                                    } catch (\Throwable $e) {
                                        $url = '#';
                                    }
                                @endphp

                                <a href="{{ $url }}"
                                   class="tc-shell-nav-link {{ $active ? 'tc-shell-nav-link-active' : '' }}"
                                   @if($active) aria-current="page" @endif>
                                    <span class="flex-1 whitespace-nowrap">{{ $item['name'] }}</span>
                                    @if(($navUpdateCounts[$item['badgeKey'] ?? ''] ?? 0) > 0)
                                        <span class="tc-shell-nav-count">{{ min(99, $navUpdateCounts[$item['badgeKey']]) }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                @if(!empty($adminNav))
                    <div class="mt-6 border-t tc-shell-divider pt-6">
                        <div class="mb-3 px-2 text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-slate-400">Admin</div>
                        <div class="space-y-1.5">
                            @foreach($adminNav as $item)
                                @php
                                    $matchPatterns = (array) $item['match'];
                                    $active = collect($matchPatterns)->contains(fn ($pattern) => str_starts_with($routeName, $pattern));
                                    try {
                                        $url = route($item['route'], $item['params']);
                                    } catch (\Throwable $e) {
                                        $url = '#';
                                    }
                                @endphp
                                <a href="{{ $url }}" class="tc-shell-nav-link {{ $active ? 'tc-shell-nav-link-active' : '' }}" @if($active) aria-current="page" @endif>
                                    <span class="flex-1 whitespace-nowrap">{{ $item['name'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </nav>

            @if($workspace)
                <div class="mt-5 border-t tc-shell-divider pt-5">
                    <div class="grid grid-cols-2 gap-2">
                        <a href="{{ route('app.workspaces.index') }}" class="tc-btn-secondary !w-full !px-3 !py-2 text-xs">Switch</a>
                        <a href="{{ route('app.settings', ['tab' => 'workspace']) }}" class="tc-btn-ghost !w-full !px-3 !py-2 text-xs">Manage</a>
                    </div>
                </div>
            @endif

            <div class="mt-5 border-t tc-shell-divider pt-5">
                <div class="relative" @keydown.escape.window="userMenuOpen = false">
                    <button type="button"
                        class="tc-shell-user-button flex w-full items-center justify-between gap-3 rounded-[1.2rem] border px-3 py-3 text-left transition"
                        @click="userMenuOpen = !userMenuOpen"
                        aria-haspopup="menu"
                        :aria-expanded="userMenuOpen.toString()">
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-slate-950">{{ $authUser?->name ?? 'Account' }}</span>
                            <span class="block truncate text-xs text-slate-500">{{ $authUser?->email ?? 'Signed in' }}</span>
                        </span>
                        <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Menu</span>
                    </button>

                    <div class="tc-shell-user-menu absolute inset-x-0 bottom-[calc(100%+0.75rem)] z-20 rounded-[1.2rem] border p-2 shadow-[0_22px_60px_-32px_rgba(15,23,42,0.38)]"
                        x-show="userMenuOpen"
                        x-transition.origin.bottom
                        @click.away="userMenuOpen = false">
                        <a href="{{ route('app.settings', ['tab' => 'profile']) }}" class="tc-shell-nav-link !rounded-[0.9rem] !px-3 !py-2.5">
                            <span>Profile</span>
                        </a>
                        <a href="{{ route('app.settings') }}" class="tc-shell-nav-link !rounded-[0.9rem] !px-3 !py-2.5">
                            <span>Settings</span>
                        </a>
                        <div class="mt-2 border-t tc-shell-divider px-2 pt-3">
                            <div class="px-1 text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-400">Button color</div>
                            <div class="mt-2 grid grid-cols-2 gap-2">
                                <button type="button" class="tc-accent-swatch" :class="currentAccent === 'orange' ? 'tc-accent-swatch-active' : ''" @click.stop="applyAccent('orange')">
                                    <span class="tc-accent-dot tc-accent-dot-orange"></span>
                                    <span>Orange</span>
                                </button>
                                <button type="button" class="tc-accent-swatch" :class="currentAccent === 'blue' ? 'tc-accent-swatch-active' : ''" @click.stop="applyAccent('blue')">
                                    <span class="tc-accent-dot tc-accent-dot-blue"></span>
                                    <span>Blue</span>
                                </button>
                                <button type="button" class="tc-accent-swatch" :class="currentAccent === 'green' ? 'tc-accent-swatch-active' : ''" @click.stop="applyAccent('green')">
                                    <span class="tc-accent-dot tc-accent-dot-green"></span>
                                    <span>Green</span>
                                </button>
                                <button type="button" class="tc-accent-swatch" :class="currentAccent === 'purple' ? 'tc-accent-swatch-active' : ''" @click.stop="applyAccent('purple')">
                                    <span class="tc-accent-dot tc-accent-dot-purple"></span>
                                    <span>Purple</span>
                                </button>
                            </div>
                            <p class="mt-2 px-1 text-[0.7rem] leading-5 text-slate-500">Changes the main app highlight color across this browser.</p>
                        </div>
                        <form method="POST" action="{{ route('logout') }}" class="mt-1">
                            @csrf
                            <button type="submit" class="tc-shell-nav-link !w-full !rounded-[0.9rem] !px-3 !py-2.5 text-red-600 hover:!bg-red-50 hover:!text-red-700">
                                <span>Sign out</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </aside>

        <div class="min-w-0 flex-1">
            <header class="tc-app-header" x-cloak :class="headerHidden ? 'tc-app-header-hidden' : 'tc-app-header-visible'" :aria-hidden="headerHidden.toString()">
                <div class="tc-app-header-fade"></div>
                <div class="px-4 pt-4 pb-1 sm:px-6 sm:pt-5 sm:pb-1 lg:px-8 lg:pt-6 lg:pb-1.5">
                    <div class="tc-page-frame">
                        <div class="tc-app-topbar tc-app-topbar-rest">
                            <div class="flex w-full items-center gap-3 sm:items-center">
                            <div class="tc-mobile-topbar-strip flex min-w-0 flex-1 items-stretch overflow-hidden rounded-[1rem] border border-slate-200/80 bg-slate-50/70 shadow-[0_18px_38px_-28px_rgba(15,23,42,0.18)] md:hidden">
                                <button type="button" class="tc-mobile-topbar-segment inline-flex min-w-0 flex-1 basis-0 items-center justify-center px-3 py-2.5 text-center text-[0.68rem] font-semibold uppercase tracking-[0.2em] transition" @click="sidebarOpen = true" aria-label="Open navigation">
                                    <span class="block leading-none">Menu</span>
                                </button>

                                @if($workspaceBreadcrumbUrl)
                                    <a href="{{ $workspaceBreadcrumbUrl }}"
                                        class="tc-mobile-topbar-segment inline-flex min-w-0 flex-1 basis-0 items-center justify-center truncate border-l border-slate-200/80 px-3 py-2.5 text-center text-[0.68rem] font-semibold uppercase tracking-[0.2em] transition">
                                        {{ $workspace?->name ?? 'tickIt' }}
                                    </a>
                                @else
                                    <span class="tc-mobile-topbar-segment inline-flex min-w-0 flex-1 basis-0 items-center justify-center truncate border-l border-slate-200/80 px-3 py-2.5 text-center text-[0.68rem] font-semibold uppercase tracking-[0.2em]">
                                        {{ $workspace?->name ?? 'tickIt' }}
                                    </span>
                                @endif

                                @if($sectionBreadcrumbUrl)
                                    <a href="{{ $sectionBreadcrumbUrl }}"
                                        class="tc-mobile-topbar-segment tc-mobile-topbar-segment-active inline-flex min-w-0 flex-1 basis-0 items-center justify-center truncate border-l border-slate-200/80 px-3 py-2.5 text-center text-[0.68rem] font-semibold uppercase tracking-[0.2em] transition">
                                        {{ $mobilePageTitle }}
                                    </a>
                                @else
                                    <span class="tc-mobile-topbar-segment tc-mobile-topbar-segment-active inline-flex min-w-0 flex-1 basis-0 items-center justify-center truncate border-l border-slate-200/80 px-3 py-2.5 text-center text-[0.68rem] font-semibold uppercase tracking-[0.2em]">
                                        {{ $mobilePageTitle }}
                                    </span>
                                @endif
                            </div>

                                <nav class="hidden min-w-0 flex-nowrap items-center gap-1.5 overflow-x-auto whitespace-nowrap pb-1 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden sm:flex-wrap sm:gap-x-2 sm:gap-y-1.5 sm:overflow-visible sm:whitespace-normal sm:pb-0 md:flex" aria-label="Page breadcrumb">
                                @if($workspaceBreadcrumbUrl)
                                    <a href="{{ $workspaceBreadcrumbUrl }}"
                                        class="max-w-[38vw] shrink-0 truncate rounded-full px-2 py-1 text-[0.62rem] font-semibold uppercase tracking-[0.22em] text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 sm:max-w-full sm:text-[0.7rem] sm:tracking-[0.24em]">
                                        {{ $workspace?->name ?? 'tickIt' }}
                                    </a>
                                @else
                                    <span class="max-w-[38vw] shrink-0 truncate px-2 py-1 text-[0.62rem] font-semibold uppercase tracking-[0.22em] text-slate-400 sm:max-w-full sm:text-[0.7rem] sm:tracking-[0.24em]">
                                        {{ $workspace?->name ?? 'tickIt' }}
                                    </span>
                                @endif

                                <span class="shrink-0 text-slate-300">/</span>

                                @if($sectionBreadcrumbUrl)
                                    <a href="{{ $sectionBreadcrumbUrl }}"
                                        class="min-w-0 max-w-[52vw] truncate rounded-full px-2 py-1 text-[0.96rem] font-semibold text-slate-950 transition hover:bg-slate-100 hover:text-slate-700 sm:max-w-full sm:text-lg">
                                        {{ $pageTitle }}
                                    </a>
                                @else
                                    <span class="min-w-0 max-w-[52vw] truncate px-2 py-1 text-[0.96rem] font-semibold text-slate-950 sm:max-w-full sm:text-lg">
                                        {{ $pageTitle }}
                                    </span>
                                @endif

                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main id="main-content" class="px-4 pb-24 pt-0 sm:px-6 sm:pb-28 sm:pt-0 lg:px-8 lg:pb-32 lg:pt-0.5">
                <div class="tc-page-frame tc-page-stack">
                    @if($browserNotificationConfig)
                        <div x-data="browserNotifications(@js($browserNotificationConfig))" x-init="init()">
                            <div x-cloak x-show="showBanner" x-transition class="tc-accent-banner rounded-[1.2rem] border px-4 py-3">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <div class="tc-accent-banner-title text-sm font-semibold">Turn on browser notifications</div>
                                <p class="tc-accent-banner-copy mt-1 text-sm leading-6">Get a heads-up when new calls, meetings, or tickets show up in this workspace.</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" class="tc-accent-button-subtle tc-btn-secondary !px-3 !py-2 text-xs" @click="dismiss()">Not now</button>
                                        <button type="button" class="tc-btn-primary !px-3 !py-2 text-xs" @click="requestPermission()">Allow notifications</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if(session('success') || session('error') || $errors->any())
                        <div class="space-y-3">
                            @if(session('success'))
                                <div class="rounded-[1.2rem] border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 shadow-[0_14px_36px_-28px_rgba(5,150,105,0.5)]">
                                    {{ session('success') }}
                                </div>
                            @endif

                            @if(session('error'))
                                <div class="rounded-[1.2rem] border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800 shadow-[0_14px_36px_-28px_rgba(220,38,38,0.4)]">
                                    {{ session('error') }}
                                </div>
                            @endif

                            @if($errors->any())
                                <div class="rounded-[1.2rem] border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
                                    {{ $errors->first() }}
                                </div>
                            @endif
                        </div>
                    @endif

                    <x-ui.page-header :title="$pageTitle" :description="$pageDescription" :eyebrow="$pageEyebrow">
                        @if($pageMeta !== '')
                            {!! $pageMeta !!}
                        @endif

                        @if(trim($__env->yieldContent('header_actions')) !== '')
                            <x-slot:actions>
                                {!! $__env->yieldContent('header_actions') !!}
                            </x-slot:actions>
                        @endif
                    </x-ui.page-header>

                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    @if($workspace && $assistantReviewPrompt)
        <div
            x-data="workspaceReviewWidget(@js($reviewWidgetConfig))"
            x-cloak
            x-show="!(sidebarOpen && window.innerWidth < 768)">
            <div class="hidden md:block">
                <div x-show="open" x-transition.origin.bottom.right class="fixed bottom-24 right-6 z-40 w-[23rem]">
                    <div class="tc-widget-card p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-500">Product review</div>
                                <div class="mt-2 text-base font-semibold text-slate-950">How did setup feel?</div>
                                <p class="mt-2 text-sm leading-6 text-slate-600">You just created <span class="font-semibold text-slate-900" x-text="assistantName"></span>. Rate the setup experience from 0 to 5, then add any quick feedback.</p>
                            </div>
                            <button type="button" class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400" @click="open = false">Close</button>
                        </div>

                        <div class="tc-review-scale mt-4">
                            <template x-for="option in options" :key="option.value">
                                <button type="button" class="tc-review-option" :class="rating === option.value ? 'tc-review-option-active' : ''" @click="rating = option.value">
                                    <span class="tc-face" :class="'tc-face-' + option.value">
                                        <span class="tc-face-mouth"></span>
                                    </span>
                                    <span x-text="option.value"></span>
                                </button>
                            </template>
                        </div>

                        <div x-show="rating !== null" x-transition class="mt-4 space-y-3">
                            <div class="tc-field">
                                <label class="tc-field-label" for="assistant-review-feedback">More feedback</label>
                                <textarea id="assistant-review-feedback" class="tc-textarea" rows="4" x-model="feedbackText" placeholder="What felt clear, confusing, slow, or missing?"></textarea>
                            </div>

                            <div class="flex items-center justify-between gap-3">
                                <p x-show="submitted" class="text-sm font-medium text-emerald-700">Thanks. Feedback saved.</p>
                                <button type="button" class="tc-btn-primary ml-auto !px-3.5 !py-2.5 text-xs" @click="submit()" :disabled="loading || rating === null">
                                    <span x-text="loading ? 'Saving...' : 'Send feedback'"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="md:hidden" x-show="open">
                <div class="fixed inset-0 z-40 bg-slate-950/45 backdrop-blur-sm" @click="open = false"></div>
                <div x-transition.origin.bottom class="fixed inset-x-0 bottom-0 z-50 rounded-t-[1.6rem] bg-white px-5 pb-6 pt-5 shadow-[0_-24px_70px_-34px_rgba(15,23,42,0.4)]">
                    <div class="mx-auto h-1.5 w-12 rounded-full bg-slate-200"></div>
                    <div class="mt-4 flex items-start justify-between gap-3">
                        <div>
                            <div class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-500">Product review</div>
                            <div class="mt-2 text-base font-semibold text-slate-950">How did setup feel?</div>
                        </div>
                        <button type="button" class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400" @click="open = false">Close</button>
                    </div>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Rate the setup for <span class="font-semibold text-slate-900" x-text="assistantName"></span>, then add anything we should improve.</p>

                    <div class="tc-review-scale mt-4">
                        <template x-for="option in options" :key="'mobile-' + option.value">
                            <button type="button" class="tc-review-option" :class="rating === option.value ? 'tc-review-option-active' : ''" @click="rating = option.value">
                                <span class="tc-face" :class="'tc-face-' + option.value">
                                    <span class="tc-face-mouth"></span>
                                </span>
                                <span x-text="option.value"></span>
                            </button>
                        </template>
                    </div>

                    <div class="mt-4 space-y-3">
                        <textarea class="tc-textarea" rows="4" x-model="feedbackText" placeholder="What should feel better here?"></textarea>
                        <button type="button" class="tc-btn-primary w-full" @click="submit()" :disabled="loading || rating === null">
                            <span x-text="loading ? 'Saving...' : 'Send feedback'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($workspace)
        <div
            x-data="workspaceHelperWidget(@js($helperWidgetConfig))"
            x-cloak
            x-show="!(sidebarOpen && window.innerWidth < 768)"
            class="fixed bottom-4 left-4 right-4 z-40 md:bottom-6 md:left-auto md:right-6 md:w-full md:max-w-sm">
            <div x-show="open" x-transition.origin.bottom.right class="mb-3 tc-helper-panel">
                <div class="border-b border-slate-200/80 px-4 py-3">
                    <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-500">AI Assistant</div>
                                <div class="mt-1 text-sm font-semibold text-slate-950">Ask what to do next or where to find something.</div>
                            </div>
                        <button type="button" class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400" @click="open = false">Close</button>
                    </div>
                </div>

                <div class="max-h-[22rem] space-y-3 overflow-y-auto px-4 py-4">
                    <template x-for="(message, index) in messages" :key="index">
                        <div>
                            <div class="tc-helper-message" :class="message.role === 'user' ? 'tc-helper-message-user' : 'tc-helper-message-assistant'" x-text="message.content"></div>
                            <div x-show="message.actions && message.actions.length" class="mt-2 flex flex-wrap gap-2">
                                <template x-for="action in message.actions" :key="action.href">
                                    <a :href="action.href" class="tc-btn-secondary !px-3 !py-2 text-xs" x-text="action.label"></a>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="border-t border-slate-200/80 px-4 py-4">
                    <form class="space-y-3" @submit.prevent="send()">
                                <textarea class="tc-textarea" rows="3" x-model="input" placeholder="Ask about calls, tickets, assistants, billing, or setup."></textarea>
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs leading-5 text-slate-500">It can answer questions and send you to the right page.</p>
                            <button type="submit" class="tc-btn-primary !px-3.5 !py-2.5 text-xs" :disabled="loading || !input.trim()">
                                <span x-text="loading ? 'Sending...' : 'Send'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <button type="button" class="tc-btn-primary tc-btn-primary-floating w-full justify-between rounded-full !px-4 !py-3" @click="toggle()">
                <span>AI Assistant</span>
                <span class="tc-accent-soft-text text-xs uppercase tracking-[0.18em]" x-text="open ? 'Close' : 'Chat'"></span>
            </button>
        </div>
    @endif

    <script>
        function browserNotifications(config) {
            return {
                showBanner: false,
                supported: false,
                permission: 'default',
                init() {
                    this.supported = 'Notification' in window;

                    if (!this.supported) {
                        return;
                    }

                    this.permission = Notification.permission;
                    this.showBanner = this.permission === 'default' && localStorage.getItem(this.dismissKey()) !== '1';
                    this.announceUpdates();
                },
                dismissKey() {
                    return `tickit-notification-banner-${config.workspaceId}`;
                },
                countKey() {
                    return `tickit-notification-counts-${config.workspaceId}`;
                },
                dismiss() {
                    this.showBanner = false;
                    localStorage.setItem(this.dismissKey(), '1');
                },
                async requestPermission() {
                    if (!this.supported) {
                        return;
                    }

                    const result = await Notification.requestPermission();
                    this.permission = result;
                    this.showBanner = false;

                    if (result === 'granted') {
                        localStorage.removeItem(this.dismissKey());
                        this.announceUpdates(true);
                        return;
                    }

                    localStorage.setItem(this.dismissKey(), '1');
                },
                announceUpdates(force = false) {
                    const currentCounts = config.counts || {};
                    const previousCounts = JSON.parse(localStorage.getItem(this.countKey()) || '{}');

                    if (this.permission === 'granted') {
                        Object.entries(currentCounts).forEach(([key, rawCount]) => {
                            const count = Number(rawCount || 0);
                            const previous = Number(previousCounts[key] || 0);
                            const delta = Math.max(0, count - previous);

                            if ((force && count > 0) || (!force && delta > 0)) {
                                const label = config.labels?.[key] || key;
                                const amount = force ? count : delta;
                                const note = new Notification('tickIt', {
                                    body: amount === 1
                                        ? `1 new ${label} needs attention.`
                                        : `${amount} new ${label}s need attention.`,
                                    tag: `tickit-${config.workspaceId}-${key}`,
                                });

                                note.onclick = () => {
                                    if (config.links?.[key]) {
                                        window.location.href = config.links[key];
                                    }
                                    window.focus();
                                };
                            }
                        });
                    }

                    localStorage.setItem(this.countKey(), JSON.stringify(currentCounts));
                },
            };
        }

        function workspaceReviewWidget(config) {
            return {
                open: !!config.enabled,
                rating: null,
                feedbackText: '',
                loading: false,
                submitted: false,
                assistantId: config.assistantId,
                assistantName: config.assistantName,
                options: [
                    { value: 0 },
                    { value: 1 },
                    { value: 2 },
                    { value: 3 },
                    { value: 4 },
                    { value: 5 },
                ],
                async submit() {
                    if (this.rating === null || this.loading) {
                        return;
                    }

                    this.loading = true;

                    try {
                        const response = await fetch(config.url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': config.csrf,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                rating: this.rating,
                                feedback_text: this.feedbackText,
                                assistant_config_id: this.assistantId,
                                category: 'assistant_setup',
                                context: {
                                    assistant_name: this.assistantName,
                                    page_title: config.pageTitle,
                                },
                            }),
                        });

                        if (!response.ok) {
                            throw new Error('Feedback could not be saved.');
                        }

                        this.submitted = true;
                        window.setTimeout(() => {
                            this.open = false;
                        }, 900);
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }

        function workspaceHelperWidget(config) {
            return {
                open: false,
                input: '',
                loading: false,
                messages: [
                    {
                        role: 'assistant',
                        content: 'Ask for the next best step, where a setting lives, or what to review in this workspace.',
                        actions: [],
                    },
                ],
                toggle() {
                    this.open = !this.open;
                },
                async send() {
                    const message = this.input.trim();
                    if (!message || this.loading) {
                        return;
                    }

                    this.messages.push({ role: 'user', content: message, actions: [] });
                    this.input = '';
                    this.loading = true;

                    try {
                        const response = await fetch(config.url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': config.csrf,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                message,
                                history: this.messages.slice(-6).map((entry) => ({
                                    role: entry.role,
                                    content: entry.content,
                                })),
                                page: config.page,
                            }),
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            throw new Error(data.error || 'The helper could not respond right now.');
                        }

                        this.messages.push({
                            role: 'assistant',
                            content: data.reply || 'I could not find a useful answer right now.',
                            actions: data.actions || [],
                        });
                    } catch (error) {
                        this.messages.push({
                            role: 'assistant',
                            content: 'I could not respond right now. Try again in a moment or open the section you need from the sidebar.',
                            actions: [],
                        });
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }
    </script>

    @stack('scripts')
</body>

</html>
