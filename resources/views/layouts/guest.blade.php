@php
    $routeName = Route::currentRouteName() ?? '';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Ticketcloser')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body style="background:#020202;color:#fff;overflow-x:hidden" class="font-[Inter,system-ui,sans-serif] antialiased">

    {{-- Three.js canvas --}}
    <canvas id="three-canvas"
        style="position:fixed;top:0;left:0;width:100%;height:100%;z-index:0;pointer-events:none"></canvas>

    {{-- Brand --}}
    <div class="fixed top-0 left-0 w-full p-6 z-50">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <a href="/" class="flex items-center gap-2">
                <span
                    class="text-[17px] font-bold tracking-tight text-white hover:opacity-80 transition-opacity">ticketcloser</span>
            </a>
            <div class="flex items-center gap-6">
                @if($routeName === 'register')
                    <a href="{{ route('login') }}"
                        class="text-[13px] font-medium text-slate-300 hover:text-white transition-colors">Sign in</a>
                @elseif($routeName === 'login')
                    <a href="{{ route('register') }}"
                        class="text-[13px] font-medium px-4 py-2 bg-[#f97316] hover:bg-[#ea580c] rounded-lg text-white transition-colors"
                        style="box-shadow: 0 0 15px rgba(249, 115, 22, 0.4);">Get started free</a>
                @endif
            </div>
        </div>
    </div>

    <div class="min-h-screen flex items-center justify-center px-4 py-12 relative z-10 pt-[100px]">
        <div class="w-full max-w-sm">
            @yield('content')
        </div>
    </div>

</body>

</html>