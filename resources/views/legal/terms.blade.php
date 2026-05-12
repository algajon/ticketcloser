@extends('layouts.guest')

@section('title', 'tickIt - Terms and Conditions')
@section('guest_eyebrow', 'Legal')
@section('guest_title', 'Terms and conditions')
@section('guest_copy', 'The rules for using tickIt, provisioning assistants, and handling workspace data.')

@section('auth_width', 'max-w-3xl')

@section('content')
    <div>
        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400">tickIt legal</div>
        <h1 class="mt-4 text-3xl font-semibold tracking-tight text-white">Terms and conditions</h1>
        <p class="mt-3 text-sm leading-6 text-slate-300">These terms apply to all accounts, workspaces, assistants, and connected integrations using the platform.</p>
    </div>

    <div class="mt-8 space-y-6 text-sm leading-7 text-slate-200">
        <section class="rounded-[1.4rem] border border-white/10 bg-white/5 p-5">
            <h2 class="text-base font-semibold text-white">1. Use of the service</h2>
            <p class="mt-3">You may use tickIt only for lawful business operations. You are responsible for the content, call flows, prompts, integrations, and team members configured inside your workspace.</p>
        </section>

        <section class="rounded-[1.4rem] border border-white/10 bg-white/5 p-5">
            <h2 class="text-base font-semibold text-white">2. Account and workspace responsibility</h2>
            <p class="mt-3">The account owner is responsible for keeping credentials secure, managing who has access to the workspace, and ensuring outbound actions triggered by assistants are appropriate for the business.</p>
        </section>

        <section class="rounded-[1.4rem] border border-white/10 bg-white/5 p-5">
            <h2 class="text-base font-semibold text-white">3. Call data and recordings</h2>
            <p class="mt-3">If you enable transcripts, recordings, or analytics, you are responsible for obtaining any disclosures, notices, or consent required under the laws that apply to your business and callers.</p>
        </section>

        <section class="rounded-[1.4rem] border border-white/10 bg-white/5 p-5">
            <h2 class="text-base font-semibold text-white">4. Acceptable use</h2>
            <p class="mt-3">You may not use the platform to violate privacy rights, impersonate others, send unlawful communications, or generate harmful, deceptive, or abusive assistant behavior.</p>
        </section>

        <section class="rounded-[1.4rem] border border-white/10 bg-white/5 p-5">
            <h2 class="text-base font-semibold text-white">5. Billing and availability</h2>
            <p class="mt-3">Paid features, usage-based billing, and third-party services such as telephony, voice providers, and calendar integrations may affect pricing, limits, and availability. tickIt may update the product as the platform evolves.</p>
        </section>

        <section class="rounded-[1.4rem] border border-white/10 bg-white/5 p-5">
            <h2 class="text-base font-semibold text-white">6. Limitation of responsibility</h2>
            <p class="mt-3">tickIt helps automate workflows, but you remain responsible for operational decisions, follow-up actions, escalations, legal compliance, and any business impact caused by your configuration choices.</p>
        </section>
    </div>
@endsection