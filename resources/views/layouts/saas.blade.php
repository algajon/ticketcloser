@php
    $routeName = Route::currentRouteName() ?? '';
    $workspace = $workspace ?? (auth()->check() ? auth()->user()->currentWorkspace() : null);

    $nav = [
        [
            'name' => 'Dashboard',
            'route' => 'app.dashboard',
            'params' => [],
            'match' => 'app.dashboard',
            'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>',
        ],
        [
            'name' => 'Cases',
            'route' => 'app.tickets.index',
            'params' => [],
            'match' => 'app.tickets.',
            'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>',
        ],
        [
            'name' => 'Assistants',
            'route' => 'app.assistant.edit',
            'params' => [$workspace],
            'match' => 'app.assistant.',
            'workspace' => true,
            'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15M14.25 3.104c.251.023.501.05.75.082M19.8 15a2.25 2.25 0 01-.659 1.591l-1.591 1.591M19.8 15l-1.5 1.5M5 14.5L3.409 16.09A2.25 2.25 0 003 17.682v1.068a2.25 2.25 0 002.25 2.25h13.5A2.25 2.25 0 0021 18.75v-1.068a2.25 2.25 0 00-.409-1.591L19.8 15"/></svg>',
        ],
        [
            'name' => 'Phone Numbers',
            'route' => 'app.phone_numbers.index',
            'params' => [$workspace],
            'match' => 'app.phone_numbers.',
            'workspace' => true,
            'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>',
        ],
        [
            'name' => 'Contacts',
            'route' => 'app.contacts.index',
            'params' => [$workspace],
            'match' => 'app.contacts.',
            'workspace' => true,
            'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>',
        ],
        [
            'name' => 'Calendar',
            'route' => 'app.calendar.index',
            'params' => [],
            'match' => 'app.calendar.',
            'workspace' => true,
            'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>',
        ],
        // [
        //     'name' => 'Billing',
        //     'route' => 'app.billing.index',
        //     'params' => [],
        //     'match' => 'app.billing.',
        //     'workspace' => true,
        //     'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" /></svg>',
        // ],
    ];

    // Admin-only nav items
    $adminNav = [];
    if (auth()->check() && auth()->user()->isAdmin()) {
        $adminNav = [
            [
                'name' => 'Admin Billing',
                'route' => 'admin.billing.index',
                'params' => [],
                'match' => 'admin.billing.',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" /></svg>',
            ],
            [
                'name' => 'Admin Presets',
                'route' => 'admin.presets.index',
                'params' => [],
                'match' => 'admin.presets.',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>',
            ],
        ];
    }
@endphp
<!doctype html>
<html lang="en" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'TicketCloser')</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>

<body class="bg-slate-50 text-slate-900 font-[Inter,system-ui,sans-serif] h-full antialiased"
    x-data="{ sidebarOpen: false }">

    {{-- Skip to content --}}
    <a href="#main-content"
        class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 z-50 px-4 py-2 rounded border border-slate-200 bg-white text-slate-900 text-sm font-medium">
        Skip to content
    </a>

    {{-- Mobile sidebar backdrop --}}
    <div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-30 md:hidden"
        @click="sidebarOpen = false">
    </div>

    {{-- ──────────────────────────────────────────────────
    LAYOUT: Sidebar (left) + Main content (right)
    ────────────────────────────────────────────────────── --}}
    <div class="min-h-screen flex">

        {{-- ── LEFT SIDEBAR ─────────────────────── --}}
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
            class="fixed md:static top-0 left-0 h-full md:h-screen w-64 flex flex-col z-40 transition-transform duration-300 ease-in-out shadow-xl md:shadow-sm bg-white border-r border-slate-200 flex-shrink-0">

            {{-- Sidebar brand & close --}}
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between bg-white">
                <a href="{{ route('app.dashboard') }}" class="flex items-center gap-2 no-underline">
                    <span class="text-lg font-bold tracking-tight text-slate-900">ticketcloser</span>
                </a>

                {{-- Mobile close --}}
                <button @click="sidebarOpen = false"
                    class="md:hidden text-slate-400 hover:text-slate-600 p-1 rounded transition-colors"
                    aria-label="Close sidebar">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1 bg-white" role="navigation"
                aria-label="Main navigation">
                @foreach($nav as $item)
                            @if(($item['workspace'] ?? false) && !$workspace)
                                @continue
                            @endif
                            @php
                                $active = str_starts_with($routeName, $item['match']);
                                try {
                                    $url = route($item['route'], $item['params']);
                                } catch (\Exception $e) {
                                    $url = '#';
                                }
                            @endphp
                            <a href="{{ $url }}" class="flex items-center gap-3 px-3 py-2 rounded-md text-sm transition-all duration-150
                                           {{ $active
                    ? 'bg-orange-50 text-orange-600 font-semibold'
                    : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 font-medium' }}"
                                @if($active) aria-current="page" @endif>

                                {{-- Icon --}}
                                <span class="{{ $active ? 'text-orange-500' : 'text-slate-400' }}">
                                    {!! $item['icon'] !!}
                                </span>

                                {{ $item['name'] }}
                            </a>
                @endforeach

                {{-- Admin section --}}
                @if(!empty($adminNav))
                    <div class="pt-3 mt-3 border-t border-slate-200">
                        <div class="px-3 mb-2 text-[10px] font-bold uppercase tracking-widest text-slate-400">Admin</div>
                        @foreach($adminNav as $item)
                            @php
                                $active = str_starts_with($routeName, $item['match']);
                                try { $url = route($item['route'], $item['params']); } catch (\Exception $e) { $url = '#'; }
                            @endphp
                            <a href="{{ $url }}" class="flex items-center gap-3 px-3 py-2 rounded-md text-sm transition-all duration-150
                                           {{ $active
                                ? 'bg-orange-50 text-orange-600 font-semibold'
                                : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 font-medium' }}"
                                @if($active) aria-current="page" @endif>
                                <span class="{{ $active ? 'text-orange-500' : 'text-slate-400' }}">{!! $item['icon'] !!}</span>
                                {{ $item['name'] }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </nav>

            {{-- Settings link --}}
            <div class="px-3 pb-1 pt-3 border-t border-slate-200 bg-white">
                <a href="{{ route('app.settings') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition-all duration-150
                          {{ str_starts_with($routeName, 'app.settings') ? 'bg-orange-50 text-orange-600 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <span class="{{ str_starts_with($routeName, 'app.settings') ? 'text-orange-500' : 'text-slate-400' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    </span>
                    Settings
                </a>
            </div>

            {{-- Workspace switcher pill --}}
            @if($workspace)
                <div class="px-4 py-4 border-t border-slate-200 bg-white">
                    <a href="{{ route('app.workspaces.index') }}"
                        class="flex items-center justify-between px-3 py-2 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors text-xs text-slate-700 font-medium shadow-sm">
                        <span class="truncate max-w-[120px]">{{ $workspace->name }}</span>
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                        </svg>
                    </a>
                </div>
            @endif

            {{-- Profile --}}
            <div class="p-4 border-t border-slate-200 bg-white">
                <div class="flex items-center gap-3">
                    <div
                        class="w-8 h-8 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center font-bold text-sm">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold text-slate-900 truncate">{{ auth()->user()->name }}</div>
                        <div class="text-xs text-slate-500 truncate">{{ auth()->user()->email }}</div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-slate-400 hover:text-orange-600 transition-colors p-1"
                            title="Sign out">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- ── MAIN CONTENT (right, flex-1) ──────────── --}}
        <div class="flex-1 min-w-0 flex flex-col relative z-10 bg-slate-50">

            {{-- Top Header Bar --}}
            <header class="border-b border-slate-200 sticky top-0 z-20 bg-white/80 backdrop-blur-md">
                <div class="px-4 sm:px-6 py-4 flex items-center gap-4">
                    {{-- Mobile menu open button --}}
                    <button @click="sidebarOpen = !sidebarOpen"
                        class="md:hidden p-1.5 rounded-md hover:bg-slate-100 text-slate-500 transition-colors"
                        aria-label="Open navigation">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>

                    {{-- Page Title --}}
                    <div class="flex-1 min-w-0">
                        <h1 class="text-xl font-bold text-slate-900 truncate">@yield('header')</h1>
                    </div>

                    {{-- Header Actions slot --}}
                    @yield('header-actions')
                </div>
            </header>

            {{-- Flash alerts --}}
            @if(session('success') || session('error') || $errors->any())
                <div class="px-4 sm:px-6 py-4 border-b border-slate-200 bg-white">
                    @if(session('success'))
                        <div
                            class="p-4 rounded-md border border-emerald-200 bg-emerald-50 text-emerald-700 text-sm font-medium">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="p-4 rounded-md border border-red-200 bg-red-50 text-red-700 text-sm font-medium">
                            {{ session('error') }}
                        </div>
                    @endif
                    @if($errors->any())
                        <div class="p-4 rounded-md border border-red-200 bg-red-50 text-red-700 text-sm font-medium">
                            {{ $errors->first() }}
                        </div>
                    @endif
                </div>
            @endif

            {{-- Page content --}}
            <main id="main-content" class="flex-1 px-4 sm:px-6 py-6 sm:py-8">
                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
</body>

</html>