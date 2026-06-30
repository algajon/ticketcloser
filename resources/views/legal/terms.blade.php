@php
    $metaTitle = 'tickIt - Terms and Conditions';
    $metaDescription = 'Terms for using tickIt, including account responsibilities, AI assistant usage, call data, integrations, billing, and acceptable use.';
    $metaCanonical = route('terms');
    $structuredData = [
        [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => 'tickIt Terms and Conditions',
            'description' => $metaDescription,
            'url' => $metaCanonical,
        ],
    ];
    $contactEmail = config('services.tickit_sales.email', 'jon@ticketcloser.online');
    $sections = [
        [
            'id' => 'agreement',
            'label' => '01',
            'title' => 'Agreement to these terms',
            'body' => [
                'These Terms and Conditions govern your access to and use of tickIt, including accounts, workspaces, voice assistants, phone numbers, transcripts, tickets, integrations, and related features.',
                'By creating an account, configuring a workspace, or using the platform, you agree to these terms on behalf of yourself or the organization you represent.',
            ],
        ],
        [
            'id' => 'accounts',
            'label' => '02',
            'title' => 'Accounts and workspace responsibility',
            'body' => [
                'You are responsible for the accuracy of the information in your workspace, the prompts and call flows you configure, and the people you invite into your account.',
                'You must keep login credentials, integration credentials, API keys, and telephony access secure. Any activity that occurs through your account is your responsibility unless caused by our own failure to protect the platform.',
            ],
        ],
        [
            'id' => 'assistants',
            'label' => '03',
            'title' => 'AI assistants and automated actions',
            'body' => [
                'tickIt helps answer calls, capture caller details, create tickets, store transcripts, route follow-up, and assist with scheduling. You remain responsible for reviewing important outputs and deciding how your team acts on them.',
                'You must not configure assistants to mislead callers, impersonate people, give regulated advice without proper oversight, make unlawful promises, or perform actions your business is not authorized to perform.',
            ],
        ],
        [
            'id' => 'call-data',
            'label' => '04',
            'title' => 'Call data, recordings, and consent',
            'body' => [
                'If you enable call recording, transcription, analytics, SMS follow-up, or integrations, you are responsible for providing notices and obtaining any consent required by the laws that apply to your business and callers.',
                'You should avoid collecting sensitive information unless it is necessary for your workflow and your business has a lawful basis to handle it.',
            ],
        ],
        [
            'id' => 'integrations',
            'label' => '05',
            'title' => 'Third-party services',
            'body' => [
                'tickIt may connect with services such as Vapi, telephony providers, voice providers, Google Calendar, Calendly, payment processors, email providers, and other tools you choose to connect.',
                'Third-party services are governed by their own terms, pricing, limits, privacy practices, uptime, and data handling. We are not responsible for failures, changes, or charges from services outside tickIt.',
            ],
        ],
        [
            'id' => 'billing',
            'label' => '06',
            'title' => 'Plans, billing, and usage',
            'body' => [
                'Some features are free, paid, usage-based, or available only on specific plans. Usage may include assistant minutes, telephony, voice, transcription, SMS, calendar actions, and other connected services.',
                'Prices, included limits, and available features may change as the product evolves. When a paid subscription or connected provider is required, you are responsible for keeping payment and provider access current.',
            ],
        ],
        [
            'id' => 'acceptable-use',
            'label' => '07',
            'title' => 'Acceptable use',
            'body' => [
                'You may use tickIt only for lawful business purposes. You may not use the platform to spam callers, harass people, violate privacy rights, bypass consent rules, abuse integrations, upload malicious content, or interfere with the service.',
                'We may suspend or restrict access if we believe a workspace creates security, legal, operational, or reputational risk for tickIt, our users, callers, or connected providers.',
            ],
        ],
        [
            'id' => 'availability',
            'label' => '08',
            'title' => 'Availability and product changes',
            'body' => [
                'We work to keep tickIt reliable, but the platform may be interrupted by maintenance, provider outages, network issues, rate limits, user configuration errors, or changes in connected services.',
                'We may update, add, remove, rename, or limit features as needed to improve the product, comply with law, protect the platform, or respond to provider changes.',
            ],
        ],
        [
            'id' => 'liability',
            'label' => '09',
            'title' => 'Disclaimers and limitation of responsibility',
            'body' => [
                'tickIt is provided as a business automation tool. It does not replace professional judgment, emergency response, legal advice, medical advice, financial advice, or regulated decision-making.',
                'To the fullest extent allowed by law, tickIt is not liable for indirect, incidental, special, consequential, or punitive damages, or for lost profits, lost data, missed calls, missed appointments, provider failures, or business decisions made from assistant outputs.',
            ],
        ],
        [
            'id' => 'termination',
            'label' => '10',
            'title' => 'Cancellation and termination',
            'body' => [
                'You may stop using tickIt at any time. We may suspend or terminate access if you violate these terms, fail to pay required fees, create unacceptable risk, or use the service in a way that harms the platform or others.',
                'After cancellation or termination, access to workspaces, data, integrations, phone numbers, and assistant configuration may be limited according to the plan, provider rules, and retention practices in effect at that time.',
            ],
        ],
        [
            'id' => 'changes',
            'label' => '11',
            'title' => 'Changes to these terms',
            'body' => [
                'We may update these terms as tickIt changes. When changes are material, we will make reasonable efforts to provide notice through the product, email, or the website.',
                'Your continued use of tickIt after updated terms take effect means you accept the updated terms.',
            ],
        ],
    ];
@endphp

@extends('layouts.marketing', [
    'metaTitle' => $metaTitle,
    'metaDescription' => $metaDescription,
    'metaCanonical' => $metaCanonical,
    'structuredData' => $structuredData,
    'navCurrent' => 'terms',
])

@section('content')
    <section class="px-5 pb-8 pt-16 lg:px-8 lg:pb-10 lg:pt-20">
        <div class="mx-auto max-w-7xl">
            <div class="tc-landing-panel tc-landing-card tc-landing-glow px-6 py-10 sm:px-10 sm:py-12">
                <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_360px] lg:items-end">
                    <div class="max-w-4xl">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-orange-300/80">Legal</div>
                        <h1 class="mt-4 text-4xl font-semibold tracking-tight text-white sm:text-5xl">Terms and Conditions</h1>
                        <p class="mt-5 max-w-3xl text-base leading-8 text-slate-300">
                            These terms explain how tickIt can be used, what you are responsible for, and how connected call, ticket, calendar, telephony, and AI services fit together.
                        </p>
                    </div>

                    <div class="rounded-[1.35rem] border border-white/10 bg-white/5 p-5">
                        <div class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-400">Current version</div>
                        <div class="mt-3 text-2xl font-semibold text-white">March 31, 2026</div>
                        <p class="mt-3 text-sm leading-6 text-slate-300">
                            Questions about these terms can be sent to
                            <a href="mailto:{{ $contactEmail }}" class="font-semibold text-orange-300 transition hover:text-orange-200">{{ $contactEmail }}</a>.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="px-5 pb-14 pt-4 lg:px-8 lg:pb-20">
        <div class="mx-auto grid max-w-7xl gap-5 lg:grid-cols-[280px_minmax(0,1fr)]">
            <aside class="hidden lg:block">
                <div class="tc-landing-panel tc-landing-card sticky top-8 p-5">
                    <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400">On this page</div>
                    <nav class="mt-5 space-y-2">
                        @foreach($sections as $section)
                            <a href="#{{ $section['id'] }}" class="block rounded-xl px-3 py-2 text-sm text-slate-300 transition hover:bg-white/6 hover:text-white">
                                {{ $section['title'] }}
                            </a>
                        @endforeach
                    </nav>
                </div>
            </aside>

            <div class="space-y-5">
                <div class="tc-landing-panel tc-landing-card p-6 sm:p-8">
                    <div class="grid gap-5 md:grid-cols-3">
                        <div>
                            <div class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-400">Use</div>
                            <p class="mt-3 text-sm leading-7 text-slate-200">Use tickIt for lawful business call handling, ticket creation, and follow-up workflows.</p>
                        </div>
                        <div>
                            <div class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-400">Responsibility</div>
                            <p class="mt-3 text-sm leading-7 text-slate-200">You control prompts, caller notices, team access, connected tools, and business decisions.</p>
                        </div>
                        <div>
                            <div class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-400">Providers</div>
                            <p class="mt-3 text-sm leading-7 text-slate-200">Voice, phone, calendar, SMS, email, and payment providers may have their own rules and costs.</p>
                        </div>
                    </div>
                </div>

                @foreach($sections as $section)
                    <article id="{{ $section['id'] }}" class="tc-landing-panel tc-landing-card scroll-mt-10 px-6 py-7 sm:px-8">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:gap-6">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-orange-300/20 bg-orange-400/10 text-sm font-semibold text-orange-200">
                                {{ $section['label'] }}
                            </div>
                            <div>
                                <h2 class="text-2xl font-semibold tracking-tight text-white">{{ $section['title'] }}</h2>
                                <div class="mt-4 space-y-4 text-sm leading-7 text-slate-300">
                                    @foreach($section['body'] as $paragraph)
                                        <p>{{ $paragraph }}</p>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach

                <section class="tc-landing-panel tc-landing-card px-6 py-8 sm:px-8">
                    <div class="max-w-3xl">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-orange-300/80">Contact</div>
                        <h2 class="mt-3 text-3xl font-semibold tracking-tight text-white">Need clarification before using tickIt?</h2>
                        <p class="mt-4 text-base leading-7 text-slate-300">
                            Email us at <a href="mailto:{{ $contactEmail }}" class="font-semibold text-orange-300 transition hover:text-orange-200">{{ $contactEmail }}</a>. If your question is about compliance, recordings, consent, or regulated workflows, you should also speak with a qualified professional for your specific business and location.
                        </p>
                    </div>
                </section>
            </div>
        </div>
    </section>
@endsection
