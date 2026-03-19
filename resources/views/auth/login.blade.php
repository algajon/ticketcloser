@extends('layouts.guest')

@section('content')
    <div class="mb-8 text-center">
        <h1 class="text-2xl font-bold text-white tracking-tight">Welcome back</h1>
        <p class="text-[14px] text-slate-400 mt-2">Sign in to your account to continue</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div class="space-y-1.5 text-left">
            <label for="email" class="block text-[13px] font-medium text-slate-300">Email address</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                autocomplete="username"
                class="w-full bg-[#0b0f19]/50 border border-white/10 rounded-lg px-3 py-2 text-white placeholder-slate-500 focus:outline-none focus:border-[#f97316] focus:ring-1 focus:ring-[#f97316] transition-all text-[14px]"
                placeholder="name@company.com" />
            @if($errors->first('email'))
                <p class="text-[13px] text-red-500 mt-1">{{ $errors->first('email') }}</p>
            @endif
        </div>

        <div class="space-y-1.5 text-left">
            <div class="flex items-center justify-between">
                <label for="password" class="block text-[13px] font-medium text-slate-300">Password</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}"
                        class="text-[12px] font-medium text-[#f97316] hover:text-[#ea580c] transition-colors">
                        Forgot password?
                    </a>
                @endif
            </div>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                class="w-full bg-[#0b0f19]/50 border border-white/10 rounded-lg px-3 py-2 text-white placeholder-slate-500 focus:outline-none focus:border-[#f97316] focus:ring-1 focus:ring-[#f97316] transition-all text-[14px]"
                placeholder="••••••••" />
            @if($errors->first('password'))
                <p class="text-[13px] text-red-500 mt-1">{{ $errors->first('password') }}</p>
            @endif
        </div>

        <div class="flex items-center pt-2">
            <input id="remember_me" type="checkbox" name="remember"
                class="w-4 h-4 rounded border-white/10 bg-[#0b0f19]/50 text-[#f97316] focus:ring-[#f97316] focus:ring-offset-[#020202]">
            <label for="remember_me" class="ml-2 block text-[13px] text-slate-400">
                Remember me
            </label>
        </div>

        <button type="submit"
            class="w-full mt-6 px-4 py-2.5 bg-[#f97316] hover:bg-[#ea580c] text-white rounded-lg font-medium text-[14px] transition-all"
            style="box-shadow: 0 0 15px rgba(249, 115, 22, 0.3);">
            Sign in
        </button>
    </form>
@endsection