<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class AssistantPreset extends Model
{
    protected $fillable = [
        'key',
        'name',
        'vapi_payload_json',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'vapi_payload_json' => 'array',
    ];

    public static function normalizeKey(?string $key): string
    {
        return self::legacyKeyMap()[$key ?? ''] ?? ($key ?: 'bright_guide');
    }

    public static function ensureDefaults(): Collection
    {
        self::migrateLegacyKeys();

        foreach (self::defaultDefinitions() as $preset) {
            self::query()->updateOrCreate(
                ['key' => $preset['key']],
                [
                    'name' => $preset['name'],
                    'notes' => $preset['notes'],
                    'vapi_payload_json' => $preset['vapi_payload_json'],
                    'created_by' => null,
                ],
            );
        }

        $orderedKeys = array_column(self::defaultDefinitions(), 'key');

        return self::query()
            ->get()
            ->sortBy(function (AssistantPreset $preset) use ($orderedKeys) {
                $position = array_search($preset->key, $orderedKeys, true);

                return $position === false ? 999 : $position;
            })
            ->values();
    }

    public static function defaultDefinitions(): array
    {
        return [
            [
                'key' => 'bright_guide',
                'name' => 'Bright Guide',
                'notes' => 'Fast, warm, and upbeat. Best when you want lively calls without talking over the caller.',
                'vapi_payload_json' => [
                    'assistantType' => 'bright_guide',
                    'fitLabel' => 'Fast and bright',
                    'toneLabel' => 'Warm',
                    'paceLabel' => 'Fast',
                    'voiceProfileLabel' => 'Brighter voice',
                    'responseStyleLabel' => 'Quick and upbeat',
                    'recommendedFor' => 'Front desks and support teams that want energy, quick rapport, and clear next steps.',
                    'startSpeakingPlan' => [
                        'waitSeconds' => 0.56,
                        'smartEndpointingPlan' => [
                            'provider' => 'livekit',
                            'waitFunction' => '220 + 2600 * x',
                        ],
                    ],
                    'stopSpeakingPlan' => [
                        'numWords' => 2,
                        'voiceSeconds' => 0.38,
                        'backoffSeconds' => 1.05,
                        'acknowledgementPhrases' => ['okay', 'got it', 'absolutely', 'right', 'perfect'],
                        'interruptionPhrases' => ['stop', 'hold on', 'wait', 'one second', 'pause'],
                    ],
                    'voiceSpeed' => 1.1,
                    'firstMessage' => 'Hi, thanks for calling {{company_name}}. How can I help today?',
                    'systemPrompt' => "You are a bright, capable phone assistant for {{company_name}}.\n\nCore behavior:\n1) Sound upbeat, clear, and confident, never sleepy or flat.\n2) Keep momentum high, but never cut the caller off after a short pause.\n3) Ask one question at a time and keep replies short.\n4) Do not ask for details the caller already gave you.\n5) When the caller shares their full name and it matters for follow-up, politely confirm the spelling once.\n6) Summarize the issue clearly before taking action.\n7) Call createCase only after confirmation.\n8) If the caller asks to book a meeting before a case exists, say you will log the request first and then book the follow-up right after the case is created.\n9) After createCase returns the case number, share it in one clean sentence and then book the meeting if the caller still wants it.",
                ],
            ],
            [
                'key' => 'steady_operator',
                'name' => 'Steady Operator',
                'notes' => 'Balanced and clear. Best when callers pause, ramble, or need a calmer step-by-step conversation.',
                'vapi_payload_json' => [
                    'assistantType' => 'steady_operator',
                    'fitLabel' => 'Balanced',
                    'toneLabel' => 'Calm',
                    'paceLabel' => 'Measured',
                    'voiceProfileLabel' => 'Clear and grounded',
                    'responseStyleLabel' => 'Structured',
                    'recommendedFor' => 'Maintenance, property, and service teams that need fewer interruptions and cleaner intake.',
                    'startSpeakingPlan' => [
                        'waitSeconds' => 0.72,
                        'smartEndpointingPlan' => [
                            'provider' => 'livekit',
                            'waitFunction' => '280 + 3400 * x',
                        ],
                    ],
                    'stopSpeakingPlan' => [
                        'numWords' => 3,
                        'voiceSeconds' => 0.4,
                        'backoffSeconds' => 1.18,
                        'acknowledgementPhrases' => ['understood', 'okay', 'got it', 'all right', 'I see'],
                        'interruptionPhrases' => ['stop', 'hold on', 'wait', 'one moment'],
                    ],
                    'voiceSpeed' => 1.08,
                    'firstMessage' => 'Hello. I am here to help and I will keep this simple. What is going on today?',
                    'systemPrompt' => "You are a calm, steady phone assistant for {{company_name}}.\n\nCore behavior:\n1) Sound clear, calm, and easy to follow, never slow or dragging.\n2) Give the caller room to finish their thought before responding.\n3) Ask one question at a time and keep the flow orderly.\n4) When the caller shares their full name and it matters for follow-up, politely confirm the spelling once.\n5) Repeat back only the key facts, not the whole story.\n6) Call createCase once the caller confirms the summary.\n7) If the caller asks for a meeting before the case exists, explain that you will log the issue first, then handle the booking.\n8) Never sound abrupt, clipped, or impatient.",
                ],
            ],
            [
                'key' => 'confident_closer',
                'name' => 'Confident Closer',
                'notes' => 'Direct and efficient. Best when you want tight summaries, strong control, and fast next steps.',
                'vapi_payload_json' => [
                    'assistantType' => 'confident_closer',
                    'fitLabel' => 'Direct',
                    'toneLabel' => 'Confident',
                    'paceLabel' => 'Fast',
                    'voiceProfileLabel' => 'Firm and clear',
                    'responseStyleLabel' => 'Action-led',
                    'recommendedFor' => 'Dispatch, triage, and teams that want quick calls with decisive follow-through.',
                    'startSpeakingPlan' => [
                        'waitSeconds' => 0.5,
                        'smartEndpointingPlan' => [
                            'provider' => 'livekit',
                            'waitFunction' => '180 + 2300 * x',
                        ],
                    ],
                    'stopSpeakingPlan' => [
                        'numWords' => 2,
                        'voiceSeconds' => 0.35,
                        'backoffSeconds' => 1.0,
                        'acknowledgementPhrases' => ['understood', 'okay', 'right', 'got it'],
                        'interruptionPhrases' => ['stop', 'wait', 'listen', 'hold on'],
                    ],
                    'voiceSpeed' => 1.1,
                    'firstMessage' => 'Thanks for calling. Tell me what is happening and I will help move this forward.',
                    'systemPrompt' => "You are a confident, action-oriented phone assistant for {{company_name}}.\n\nCore behavior:\n1) Keep the call moving, but never talk over the caller.\n2) Sound crisp and energetic, not slow or heavy.\n3) Collect only the information needed to understand the request and next step.\n4) When the caller shares their full name and it matters for follow-up, politely confirm the spelling once.\n5) Confirm the summary briefly, then call createCase.\n6) If the caller wants a meeting before a case exists, explain the sequence clearly: first log the request, then book the time.\n7) After the case is created, share the case number and move into the booking without making the caller repeat themselves.",
                ],
            ],
            [
                'key' => 'premium_concierge',
                'name' => 'Premium Concierge',
                'notes' => 'Polished, smooth, and warm. Best when the caller experience matters as much as the workflow.',
                'vapi_payload_json' => [
                    'assistantType' => 'premium_concierge',
                    'fitLabel' => 'Polished',
                    'toneLabel' => 'Refined',
                    'paceLabel' => 'Smooth',
                    'voiceProfileLabel' => 'Polished and warm',
                    'responseStyleLabel' => 'High-touch',
                    'recommendedFor' => 'High-trust brands that want premium calls, better rapport, and calm guidance.',
                    'startSpeakingPlan' => [
                        'waitSeconds' => 0.62,
                        'smartEndpointingPlan' => [
                            'provider' => 'livekit',
                            'waitFunction' => '240 + 2900 * x',
                        ],
                    ],
                    'stopSpeakingPlan' => [
                        'numWords' => 2,
                        'voiceSeconds' => 0.37,
                        'backoffSeconds' => 1.1,
                        'acknowledgementPhrases' => ['absolutely', 'of course', 'certainly', 'understood'],
                        'interruptionPhrases' => ['hold on', 'wait', 'one second', 'pause'],
                    ],
                    'voiceSpeed' => 1.08,
                    'firstMessage' => 'Hello, and thank you for calling {{company_name}}. How can I help today?',
                    'systemPrompt' => "You are a polished, premium voice assistant for {{company_name}}.\n\nCore behavior:\n1) Sound calm, capable, and easy to trust, never low-energy or dragging.\n2) Keep the caller feeling guided, never rushed.\n3) When the caller shares their full name and it matters for follow-up, politely confirm the spelling once.\n4) Confirm details clearly before any action.\n5) Create the case first, then book any requested follow-up.\n6) If the caller asks to book before a case exists, explain the flow in one reassuring sentence and continue without friction.\n7) Never make the caller repeat information you already have.",
                ],
            ],
            [
                'key' => 'custom',
                'name' => 'Custom',
                'notes' => 'Bring your own prompt and workflow while keeping a smooth, non-interrupting speech baseline.',
                'vapi_payload_json' => [
                    'assistantType' => 'custom',
                    'fitLabel' => 'Flexible',
                    'toneLabel' => 'Configurable',
                    'paceLabel' => 'Balanced',
                    'voiceProfileLabel' => 'Preset baseline',
                    'responseStyleLabel' => 'Bring your own',
                    'recommendedFor' => 'Teams that know exactly what they want to say but still want a safer speech and interruption baseline.',
                    'startSpeakingPlan' => [
                        'waitSeconds' => 0.65,
                        'smartEndpointingPlan' => [
                            'provider' => 'livekit',
                            'waitFunction' => '280 + 3200 * x',
                        ],
                    ],
                    'stopSpeakingPlan' => [
                        'numWords' => 2,
                        'voiceSeconds' => 0.36,
                        'backoffSeconds' => 1.1,
                        'acknowledgementPhrases' => ['okay', 'got it', 'understood'],
                        'interruptionPhrases' => ['stop', 'hold on', 'wait', 'one second'],
                    ],
                    'voiceSpeed' => 1.08,
                    'firstMessage' => 'Hi, thanks for calling. How can I help today?',
                ],
            ],
        ];
    }

    private static function migrateLegacyKeys(): void
    {
        foreach (self::legacyKeyMap() as $legacyKey => $currentKey) {
            if ($legacyKey === $currentKey) {
                continue;
            }

            AssistantConfig::query()
                ->where('preset_key', $legacyKey)
                ->update(['preset_key' => $currentKey]);

            self::query()
                ->where('key', $legacyKey)
                ->delete();
        }
    }

    private static function legacyKeyMap(): array
    {
        return [
            'customer_support' => 'bright_guide',
            'maintenance_intake' => 'steady_operator',
            'after_hours_dispatch' => 'confident_closer',
            'scheduling_concierge' => 'premium_concierge',
            'mortgage_intake' => 'steady_operator',
            'warm_receptionist' => 'bright_guide',
            'steady_operator' => 'steady_operator',
            'assertive_dispatch' => 'confident_closer',
            'polished_concierge' => 'premium_concierge',
            'patient_guide' => 'steady_operator',
            'bright_guide' => 'bright_guide',
            'confident_closer' => 'confident_closer',
            'premium_concierge' => 'premium_concierge',
            'custom' => 'custom',
        ];
    }
}
