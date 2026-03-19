@extends('layouts.guest')

@section('content')
    <div class="mb-8 text-center">
        <h1 class="text-2xl font-bold text-white tracking-tight">Create an account</h1>
        <p class="text-[14px] text-slate-400 mt-2">Get started with ticketcloser for free.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div class="space-y-1.5 text-left">
            <label for="name" class="block text-[13px] font-medium text-slate-300">Full name</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                class="w-full bg-[#0b0f19]/50 border border-white/10 rounded-lg px-3 py-2 text-white placeholder-slate-500 focus:outline-none focus:border-[#f97316] focus:ring-1 focus:ring-[#f97316] transition-all text-[14px]"
                placeholder="John Doe" />
            @if($errors->first('name'))
                <p class="text-[13px] text-red-500 mt-1">{{ $errors->first('name') }}</p>
            @endif
        </div>

        <div class="space-y-1.5 text-left">
            <label for="email" class="block text-[13px] font-medium text-slate-300">Email address</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                class="w-full bg-[#0b0f19]/50 border border-white/10 rounded-lg px-3 py-2 text-white placeholder-slate-500 focus:outline-none focus:border-[#f97316] focus:ring-1 focus:ring-[#f97316] transition-all text-[14px]"
                placeholder="name@company.com" />
            @if($errors->first('email'))
                <p class="text-[13px] text-red-500 mt-1">{{ $errors->first('email') }}</p>
            @endif
        </div>

        <div class="space-y-1.5 text-left">
            <label for="password" class="block text-[13px] font-medium text-slate-300">Password</label>
            <input id="password" type="password" name="password" required autocomplete="new-password"
                class="w-full bg-[#0b0f19]/50 border border-white/10 rounded-lg px-3 py-2 text-white placeholder-slate-500 focus:outline-none focus:border-[#f97316] focus:ring-1 focus:ring-[#f97316] transition-all text-[14px]"
                placeholder="••••••••" />
            @if($errors->first('password'))
                <p class="text-[13px] text-red-500 mt-1">{{ $errors->first('password') }}</p>
            @endif
        </div>

        <div class="space-y-1.5 text-left">
            <label for="password_confirmation" class="block text-[13px] font-medium text-slate-300">Confirm password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required
                autocomplete="new-password"
                class="w-full bg-[#0b0f19]/50 border border-white/10 rounded-lg px-3 py-2 text-white placeholder-slate-500 focus:outline-none focus:border-[#f97316] focus:ring-1 focus:ring-[#f97316] transition-all text-[14px]"
                placeholder="••••••••" />
            @if($errors->first('password_confirmation'))
                <p class="text-[13px] text-red-500 mt-1">{{ $errors->first('password_confirmation') }}</p>
            @endif
        </div>

        <button type="submit"
            class="w-full mt-6 px-4 py-2.5 bg-[#f97316] hover:bg-[#ea580c] text-white rounded-lg font-medium text-[14px] transition-all"
            style="box-shadow: 0 0 15px rgba(249, 115, 22, 0.3);">
            Create account
        </button>
    </form>
@endsection