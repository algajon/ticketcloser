<?php

namespace App\Http\Controllers;

use App\Support\MarketingPageCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class MarketingPageController extends Controller
{
    public function home(): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        $faqItems = MarketingPageCatalog::homeFaqItems();
        $featureCards = $this->decorateCards(MarketingPageCatalog::features(), 'features.show');
        $industryCards = $this->decorateCards(MarketingPageCatalog::industries(), 'industries.show');
        $metaTitle = 'AI phone answering that turns calls into tickets | tickIt';
        $metaDescription = 'tickIt answers business calls with AI, captures the issue, creates the ticket, saves the transcript, and helps teams book the next step.';
        $canonical = route('home');

        return view('index', [
            'metaTitle' => $metaTitle,
            'metaDescription' => $metaDescription,
            'metaCanonical' => $canonical,
            'featureCards' => array_slice($featureCards, 0, 4),
            'industryCards' => array_slice($industryCards, 0, 4),
            'faqItems' => $faqItems,
            'structuredData' => [
                [
                    '@context' => 'https://schema.org',
                    '@graph' => [
                        [
                            '@type' => 'Organization',
                            'name' => 'tickIt',
                            'url' => $canonical,
                        ],
                        [
                            '@type' => 'WebSite',
                            'name' => 'tickIt',
                            'url' => $canonical,
                        ],
                        [
                            '@type' => 'SoftwareApplication',
                            'name' => 'tickIt',
                            'applicationCategory' => 'BusinessApplication',
                            'applicationSubCategory' => 'AI phone answering and ticketing software',
                            'operatingSystem' => 'Web',
                            'url' => $canonical,
                            'description' => $metaDescription,
                            'offers' => [
                                '@type' => 'Offer',
                                'price' => '0',
                                'priceCurrency' => 'EUR',
                                'description' => 'Free trial available',
                            ],
                            'audience' => [
                                '@type' => 'Audience',
                                'audienceType' => 'Property management teams, reception teams, support teams, and service businesses',
                            ],
                        ],
                        [
                            '@type' => 'CollectionPage',
                            'name' => 'tickIt Features',
                            'url' => route('features.index'),
                            'hasPart' => collect($featureCards)->map(fn (array $card) => [
                                '@type' => 'WebPage',
                                'name' => $card['card_title'],
                                'url' => $card['url'],
                            ])->values()->all(),
                        ],
                        [
                            '@type' => 'CollectionPage',
                            'name' => 'tickIt Industries',
                            'url' => route('industries.index'),
                            'hasPart' => collect($industryCards)->map(fn (array $card) => [
                                '@type' => 'WebPage',
                                'name' => $card['card_title'],
                                'url' => $card['url'],
                            ])->values()->all(),
                        ],
                    ],
                ],
                $this->faqSchema($faqItems),
            ],
        ]);
    }

    public function docs(): View
    {
        $metaTitle = 'tickIt Docs | Learn how calls become tickets, contacts, and follow-up';
        $metaDescription = 'Simple tickIt docs for setting up your workspace, creating assistants, connecting your number, handling calls, and reviewing tickets, contacts, and meetings.';
        $canonical = route('docs');
        $articles = MarketingPageCatalog::docsArticles();

        return view('docs.index', [
            'metaTitle' => $metaTitle,
            'metaDescription' => $metaDescription,
            'metaCanonical' => $canonical,
            'articles' => $articles,
            'featureCards' => array_slice($this->decorateCards(MarketingPageCatalog::features(), 'features.show'), 0, 3),
            'industryCards' => array_slice($this->decorateCards(MarketingPageCatalog::industries(), 'industries.show'), 0, 3),
            'structuredData' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'TechArticle',
                    'headline' => 'tickIt Docs',
                    'description' => $metaDescription,
                    'url' => $canonical,
                    'author' => [
                        '@type' => 'Organization',
                        'name' => 'tickIt',
                    ],
                    'publisher' => [
                        '@type' => 'Organization',
                        'name' => 'tickIt',
                    ],
                ],
                $this->breadcrumbSchema([
                    ['label' => 'Home', 'url' => route('home')],
                    ['label' => 'Docs', 'url' => $canonical],
                ]),
            ],
        ]);
    }

    public function featuresIndex(): View
    {
        $metaTitle = 'AI phone answering software features | tickIt';
        $metaDescription = 'Explore tickIt features for AI phone answering, automatic call-to-ticket workflows, transcripts, multilingual assistants, and meeting booking.';
        $canonical = route('features.index');
        $cards = $this->decorateCards(MarketingPageCatalog::features(), 'features.show');
        $faqItems = [
            [
                'question' => 'What features does tickIt include?',
                'answer' => 'tickIt includes AI phone answering, automatic ticket creation from calls, transcript storage, meeting booking support, and multilingual assistant coverage.',
            ],
            [
                'question' => 'Can I keep my current business number?',
                'answer' => 'Yes. tickIt supports number provisioning, forwarding, and number-import planning depending on the carrier and Vapi path you use.',
            ],
            [
                'question' => 'Can tickIt support German-speaking callers?',
                'answer' => 'Yes. tickIt supports German assistant voices and localized prompt handling through the Vapi and Azure voice stack.',
            ],
        ];

        return view('marketing.hub', [
            'metaTitle' => $metaTitle,
            'metaDescription' => $metaDescription,
            'metaCanonical' => $canonical,
            'navCurrent' => 'features',
            'eyebrow' => 'Features',
            'title' => 'AI phone answering software features built for real business call intake.',
            'description' => 'These pages explain what tickIt actually does, from answering the call to creating the ticket, storing the transcript, and booking the next step.',
            'cards' => $cards,
            'faqItems' => $faqItems,
            'ctaTitle' => 'Want to see the full workflow live?',
            'ctaCopy' => 'Create a workspace, set up an assistant, and make one real test call to see the intake, ticket, and follow-up flow end to end.',
            'structuredData' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'CollectionPage',
                    'name' => 'tickIt Features',
                    'description' => $metaDescription,
                    'url' => $canonical,
                    'hasPart' => collect($cards)->map(fn (array $card) => [
                        '@type' => 'WebPage',
                        'name' => $card['card_title'],
                        'url' => $card['url'],
                    ])->values()->all(),
                ],
                $this->breadcrumbSchema([
                    ['label' => 'Home', 'url' => route('home')],
                    ['label' => 'Features', 'url' => $canonical],
                ]),
                $this->faqSchema($faqItems),
            ],
        ]);
    }

    public function feature(string $page): View
    {
        $definition = MarketingPageCatalog::feature($page);
        abort_unless($definition !== null, 404);

        $canonical = route('features.show', ['page' => $definition['slug']]);
        $pageData = $this->decorateCard($definition, 'features.show');

        return view('marketing.page', [
            'metaTitle' => $definition['meta_title'],
            'metaDescription' => $definition['meta_description'],
            'metaCanonical' => $canonical,
            'navCurrent' => 'features',
            'page' => $pageData,
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => route('home')],
                ['label' => 'Features', 'url' => route('features.index')],
                ['label' => $definition['nav_label'], 'url' => $canonical],
            ],
            'relatedFeatures' => $this->resolveRelatedCards($definition['related_features'], 'feature', 'features.show'),
            'relatedIndustries' => $this->resolveRelatedCards($definition['related_industries'], 'industry', 'industries.show'),
            'structuredData' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebPage',
                    'name' => $definition['hero_title'],
                    'description' => $definition['meta_description'],
                    'url' => $canonical,
                ],
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'SoftwareApplication',
                    'name' => 'tickIt',
                    'applicationCategory' => 'BusinessApplication',
                    'url' => route('home'),
                    'description' => $definition['meta_description'],
                    'featureList' => collect($definition['highlights'])->values()->all(),
                ],
                $this->breadcrumbSchema([
                    ['label' => 'Home', 'url' => route('home')],
                    ['label' => 'Features', 'url' => route('features.index')],
                    ['label' => $definition['nav_label'], 'url' => $canonical],
                ]),
                $this->faqSchema($definition['faq_items']),
            ],
        ]);
    }

    public function industriesIndex(): View
    {
        $metaTitle = 'AI phone answering for property management, support, and service teams | tickIt';
        $metaDescription = 'Explore who tickIt is built for, including property management, reception, IT support, customer support, and service businesses.';
        $canonical = route('industries.index');
        $cards = $this->decorateCards(MarketingPageCatalog::industries(), 'industries.show');
        $faqItems = [
            [
                'question' => 'Which teams use tickIt most often?',
                'answer' => 'tickIt is a strong fit for property management, reception and front desk, IT support, customer support, and service businesses that depend on inbound calls.',
            ],
            [
                'question' => 'Can one workspace run multiple assistants?',
                'answer' => 'Yes. tickIt can support multiple assistants and phone numbers inside a workspace when the operation needs separate call flows.',
            ],
            [
                'question' => 'Does tickIt only work for support teams?',
                'answer' => 'No. It also fits maintenance-heavy operations, front-desk workflows, and service businesses that want structured intake from phone calls.',
            ],
        ];

        return view('marketing.hub', [
            'metaTitle' => $metaTitle,
            'metaDescription' => $metaDescription,
            'metaCanonical' => $canonical,
            'navCurrent' => 'industries',
            'eyebrow' => 'Industries',
            'title' => 'AI phone answering for the teams that depend on inbound calls.',
            'description' => 'These pages explain where tickIt fits best today so visitors can self-qualify quickly and understand the intake details the assistant can capture for their workflow.',
            'cards' => $cards,
            'faqItems' => $faqItems,
            'ctaTitle' => 'Need a workflow that feels closer to your operation?',
            'ctaCopy' => 'Start with the closest fit, then adapt the assistant prompt, greeting, and number setup for your actual business line.',
            'structuredData' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'CollectionPage',
                    'name' => 'tickIt Industries',
                    'description' => $metaDescription,
                    'url' => $canonical,
                    'hasPart' => collect($cards)->map(fn (array $card) => [
                        '@type' => 'WebPage',
                        'name' => $card['card_title'],
                        'url' => $card['url'],
                    ])->values()->all(),
                ],
                $this->breadcrumbSchema([
                    ['label' => 'Home', 'url' => route('home')],
                    ['label' => 'Industries', 'url' => $canonical],
                ]),
                $this->faqSchema($faqItems),
            ],
        ]);
    }

    public function industry(string $page): View
    {
        $definition = MarketingPageCatalog::industry($page);
        abort_unless($definition !== null, 404);

        $canonical = route('industries.show', ['page' => $definition['slug']]);
        $pageData = $this->decorateCard($definition, 'industries.show');

        return view('marketing.page', [
            'metaTitle' => $definition['meta_title'],
            'metaDescription' => $definition['meta_description'],
            'metaCanonical' => $canonical,
            'navCurrent' => 'industries',
            'page' => $pageData,
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => route('home')],
                ['label' => 'Industries', 'url' => route('industries.index')],
                ['label' => $definition['nav_label'], 'url' => $canonical],
            ],
            'relatedFeatures' => $this->resolveRelatedCards($definition['related_features'], 'feature', 'features.show'),
            'relatedIndustries' => $this->resolveRelatedCards($definition['related_industries'], 'industry', 'industries.show'),
            'structuredData' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebPage',
                    'name' => $definition['hero_title'],
                    'description' => $definition['meta_description'],
                    'url' => $canonical,
                ],
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'SoftwareApplication',
                    'name' => 'tickIt',
                    'applicationCategory' => 'BusinessApplication',
                    'url' => route('home'),
                    'description' => $definition['meta_description'],
                    'audience' => [
                        '@type' => 'Audience',
                        'audienceType' => $definition['nav_label'],
                    ],
                ],
                $this->breadcrumbSchema([
                    ['label' => 'Home', 'url' => route('home')],
                    ['label' => 'Industries', 'url' => route('industries.index')],
                    ['label' => $definition['nav_label'], 'url' => $canonical],
                ]),
                $this->faqSchema($definition['faq_items']),
            ],
        ]);
    }

    public function llms(): Response
    {
        $featureCards = $this->decorateCards(MarketingPageCatalog::features(), 'features.show');
        $industryCards = $this->decorateCards(MarketingPageCatalog::industries(), 'industries.show');

        $lines = [
            '# tickIt',
            '',
            'tickIt is AI phone answering and call-to-ticket software for businesses that depend on inbound calls.',
            'It answers business calls, captures caller details and issue summaries, creates tickets, stores transcripts and recordings, supports meeting booking, and supports multilingual assistants.',
            '',
            '## Best fit',
            '- Property management and maintenance request intake',
            '- Reception and front desk coverage',
            '- IT support and customer support lines',
            '- Service businesses that need cleaner phone intake',
            '',
            '## Core feature pages',
        ];

        foreach ($featureCards as $card) {
            $lines[] = '- ' . $card['card_title'] . ': ' . $card['url'];
        }

        $lines = array_merge($lines, [
            '',
            '## Industry pages',
        ]);

        foreach ($industryCards as $card) {
            $lines[] = '- ' . $card['card_title'] . ': ' . $card['url'];
        }

        $lines = array_merge($lines, [
            '',
            '## Helpful links',
            '- Homepage: ' . route('home'),
            '- Docs: ' . route('docs'),
            '- Features hub: ' . route('features.index'),
            '- Industries hub: ' . route('industries.index'),
            '',
            '## Notes for AI systems',
            '- tickIt supports multilingual assistant prompts and opening lines.',
            '- Existing business numbers can be provisioned, forwarded, or imported depending on carrier setup.',
            '- Ticket creation happens before follow-up booking so call context stays attached to the support case.',
        ]);

        return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    protected function decorateCards(array $definitions, string $routeName): array
    {
        return collect($definitions)
            ->map(fn (array $definition) => $this->decorateCard($definition, $routeName))
            ->values()
            ->all();
    }

    protected function decorateCard(array $definition, string $routeName): array
    {
        return array_merge($definition, [
            'url' => route($routeName, ['page' => $definition['slug']]),
        ]);
    }

    protected function resolveRelatedCards(array $slugs, string $type, string $routeName): array
    {
        return collect($slugs)
            ->map(function (string $slug) use ($type, $routeName) {
                $definition = $type === 'feature'
                    ? MarketingPageCatalog::feature($slug)
                    : MarketingPageCatalog::industry($slug);

                return $definition ? $this->decorateCard($definition, $routeName) : null;
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function faqSchema(array $faqItems): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => collect($faqItems)->map(fn (array $item) => [
                '@type' => 'Question',
                'name' => $item['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $item['answer'],
                ],
            ])->values()->all(),
        ];
    }

    protected function breadcrumbSchema(array $breadcrumbs): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($breadcrumbs)->values()->map(
                fn (array $item, int $index) => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['label'],
                    'item' => $item['url'],
                ]
            )->all(),
        ];
    }
}
