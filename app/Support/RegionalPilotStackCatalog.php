<?php

namespace App\Support;

use App\Models\Workspace;

class RegionalPilotStackCatalog
{
    public const GLOBAL = 'global';
    public const UAE = 'uae';

    public static function marketOptions(): array
    {
        return [
            [
                'value' => self::GLOBAL,
                'label' => 'Global / United States',
                'description' => 'Best if you want the fastest default setup and instant US test numbers.',
            ],
            [
                'value' => self::UAE,
                'label' => 'United Arab Emirates',
                'description' => 'Use Arabic-ready speech and a local UAE number through your own provider.',
            ],
        ];
    }

    public static function normalizeMarket(?string $market): string
    {
        $market = strtolower(trim((string) $market));

        return in_array($market, [self::GLOBAL, self::UAE], true) ? $market : self::GLOBAL;
    }

    public static function languageOptions(?string $market = null): array
    {
        return collect(self::orderedLanguageCodes($market))
            ->map(function (string $code) {
                $definition = self::languageDefinitions()[$code];

                return [
                    'value' => $code,
                    'label' => $definition['label'],
                ];
            })
            ->values()
            ->all();
    }

    public static function supportedLanguageCodes(?string $market = null): array
    {
        return self::orderedLanguageCodes($market);
    }

    public static function defaultLanguageForMarket(?string $market, ?string $fallback = 'en-US'): string
    {
        return match (self::normalizeMarket($market)) {
            self::UAE => 'ar-AE',
            default => self::normalizeLanguageCode($fallback, 'en-US') ?: 'en-US',
        };
    }

    public static function normalizeLanguageCode(?string $languageCode, ?string $fallback = null): ?string
    {
        $languageCode = strtolower(trim((string) $languageCode));
        $fallback = trim((string) $fallback);

        if ($languageCode === '') {
            return $fallback !== '' ? self::normalizeLanguageCode($fallback) : null;
        }

        if (in_array($languageCode, ['multi', 'multilingual'], true)) {
            return 'multi';
        }

        $aliasMap = self::languageAliasMap();
        if (array_key_exists($languageCode, $aliasMap)) {
            return $aliasMap[$languageCode];
        }

        $primary = explode('-', $languageCode)[0] ?? $languageCode;
        if (array_key_exists($primary, $aliasMap)) {
            return $aliasMap[$primary];
        }

        if ($fallback !== '') {
            return self::normalizeLanguageCode($fallback);
        }

        return null;
    }

    public static function languageLabel(?string $languageCode, ?string $fallback = null): ?string
    {
        $languageCode = self::normalizeLanguageCode($languageCode, $fallback);

        if ($languageCode === null) {
            return null;
        }

        if ($languageCode === 'multi') {
            return 'Multilingual';
        }

        return self::languageDefinitions()[$languageCode]['label'] ?? strtoupper(str_replace('-', ' ', $languageCode));
    }

    public static function defaultFirstMessage(?string $languageCode = null, string $context = 'support'): string
    {
        $languageCode = self::normalizeLanguageCode($languageCode, 'en-US') ?: 'en-US';
        $messages = self::firstMessageCatalog($context);

        return $messages[$languageCode] ?? $messages['en-US'];
    }

    public static function knownCallerSuffix(?string $contactName, ?string $languageCode = null): string
    {
        $contactName = trim((string) $contactName);

        if ($contactName === '') {
            return '';
        }

        $shortName = self::shortContactName($contactName);
        $languageCode = self::normalizeLanguageCode($languageCode, 'en-US') ?: 'en-US';

        return match ($languageCode) {
            'ar-AE' => ' يسعدني التحدث معك مرة أخرى يا ' . $shortName . '.',
            'es-ES' => ' Me alegra hablar contigo de nuevo, ' . $shortName . '.',
            'fr-FR', 'fr-CA' => ' Ravi de vous reparler, ' . $shortName . '.',
            'de-DE' => ' Schön, wieder mit Ihnen zu sprechen, ' . $shortName . '.',
            'sq-AL' => ' Më vjen mirë që flasim përsëri, ' . $shortName . '.',
            'hi-IN' => ' आपसे फिर बात करके खुशी हुई, ' . $shortName . '.',
            'bn-BD' => ' আপনার সঙ্গে আবার কথা বলে ভালো লাগছে, ' . $shortName . '.',
            'zh-CN' => ' 很高兴再次和您通话，' . $shortName . '。',
            'pt-BR' => ' É bom falar com você novamente, ' . $shortName . '.',
            'ru-RU' => ' Рад снова с вами поговорить, ' . $shortName . '.',
            'ur-PK' => ' آپ سے دوبارہ بات کر کے خوشی ہوئی، ' . $shortName . '۔',
            'id-ID' => ' Senang bisa berbicara dengan Anda lagi, ' . $shortName . '.',
            'ja-JP' => ' またお話しできてうれしいです、' . $shortName . 'さん。',
            'ko-KR' => ' 다시 통화하게 되어 반갑습니다, ' . $shortName . '님.',
            default => ' Nice to speak with you again, ' . $shortName . '.',
        };
    }

    public static function transcriberProfile(?string $languageCode): array
    {
        $languageCode = self::normalizeLanguageCode($languageCode, 'en-US') ?: 'en-US';

        if ($languageCode === 'multi') {
            return [
                'provider' => 'deepgram',
                'model' => 'nova-3',
                'language' => 'multi',
                'label' => 'Deepgram Nova-3 multilingual',
                'fallback' => null,
            ];
        }

        $definition = self::languageDefinitions()[$languageCode] ?? self::languageDefinitions()['en-US'];

        $transcriber = $definition['transcriber'];

        return [
            'provider' => $transcriber['provider'] ?? 'deepgram',
            'model' => $transcriber['model'] ?? null,
            'language' => $transcriber['language'],
            'label' => $transcriber['label'],
            'language_behaviour' => $transcriber['language_behaviour'] ?? null,
            'audio_enhancer' => $transcriber['audio_enhancer'] ?? null,
            'receive_partial_transcripts' => $transcriber['receive_partial_transcripts'] ?? null,
            'region' => $transcriber['region'] ?? null,
            'fallback' => $transcriber['fallback'] ?? (isset($transcriber['fallback_language'])
                ? [
                    'provider' => 'azure',
                    'language' => $transcriber['fallback_language'],
                ]
                : null),
        ];
    }

    public static function standardVoiceProfile(?string $languageCode, ?string $presetKey = null, ?string $market = null): ?array
    {
        $market = self::normalizeMarket($market);
        $languageCode = self::normalizeLanguageCode($languageCode, self::defaultLanguageForMarket($market));

        if (! $languageCode || $languageCode === 'multi') {
            return null;
        }

        if ($market === self::UAE && str_starts_with($languageCode, 'en-')) {
            $languageCode = 'en-GB';
        }

        $definition = self::languageDefinitions()[$languageCode] ?? null;
        $voices = $definition['standard_voices'] ?? null;

        if (! is_array($voices) || $voices === []) {
            return null;
        }

        $profileKey = in_array((string) $presetKey, ['steady_operator', 'confident_closer'], true)
            ? 'operator'
            : 'default';

        return $voices[$profileKey] ?? $voices['default'] ?? null;
    }

    public static function supportsCuratedStandardVoice(?string $languageCode, ?string $market = null): bool
    {
        $profile = self::standardVoiceProfile($languageCode, null, $market);

        return is_array($profile) && ($profile['provider'] ?? null) === 'azure';
    }

    public static function voiceCatalog(array $elevenLabsVoiceIds = []): array
    {
        $voices = [
            ['voiceId' => 'Emma', 'name' => 'Emma', 'provider' => 'vapi', 'language' => 'en-US', 'role' => 'default', 'style' => 'Clean, friendly front-desk voice', 'priceMetric' => '~$0.01/min voice'],
            ['voiceId' => 'Clara', 'name' => 'Clara', 'provider' => 'vapi', 'language' => 'en-US', 'role' => 'premium', 'style' => 'Warm concierge voice', 'priceMetric' => '~$0.01/min voice'],
            ['voiceId' => 'Savannah', 'name' => 'Savannah', 'provider' => 'vapi', 'language' => 'en-US', 'role' => 'operator', 'style' => 'Steady support operator', 'priceMetric' => '~$0.01/min voice'],
            ['voiceId' => 'Rohan', 'name' => 'Rohan', 'provider' => 'vapi', 'language' => 'en-US', 'role' => 'operator', 'style' => 'Measured operator voice', 'priceMetric' => '~$0.01/min voice'],
            ['voiceId' => 'Elliot', 'name' => 'Elliot', 'provider' => 'vapi', 'language' => 'en-US', 'role' => 'operator', 'style' => 'Confident closer voice', 'priceMetric' => '~$0.01/min voice'],
            ['voiceId' => 'Kai', 'name' => 'Kai', 'provider' => 'vapi', 'language' => 'en-US', 'role' => 'operator', 'style' => 'Calm intake voice', 'priceMetric' => '~$0.01/min voice'],
            ['voiceId' => 'Nico', 'name' => 'Nico', 'provider' => 'vapi', 'language' => 'en-US', 'role' => 'default', 'style' => 'Neutral assistant voice', 'priceMetric' => '~$0.01/min voice'],
            ['voiceId' => 'Neil', 'name' => 'Neil', 'provider' => 'vapi', 'language' => 'en-US', 'role' => 'default', 'style' => 'Grounded assistant voice', 'priceMetric' => '~$0.01/min voice'],

            ['voiceId' => 'marin', 'name' => 'Marin', 'provider' => 'openai', 'language' => 'multi', 'role' => 'premium', 'style' => 'Best-quality OpenAI voice; polished and natural', 'priceMetric' => 'TTS: $15/1M chars'],
            ['voiceId' => 'cedar', 'name' => 'Cedar', 'provider' => 'openai', 'language' => 'multi', 'role' => 'operator', 'style' => 'Best-quality OpenAI voice; steady and clear', 'priceMetric' => 'TTS: $15/1M chars'],
            ['voiceId' => 'alloy', 'name' => 'Alloy', 'provider' => 'openai', 'language' => 'multi', 'role' => 'operator', 'style' => 'Balanced and familiar', 'priceMetric' => 'TTS: $15/1M chars'],
            ['voiceId' => 'ash', 'name' => 'Ash', 'provider' => 'openai', 'language' => 'multi', 'role' => 'operator', 'style' => 'Low-key and controlled', 'priceMetric' => 'TTS: $15/1M chars'],
            ['voiceId' => 'ballad', 'name' => 'Ballad', 'provider' => 'openai', 'language' => 'multi', 'role' => 'premium', 'style' => 'Expressive and polished', 'priceMetric' => 'TTS: $15/1M chars'],
            ['voiceId' => 'coral', 'name' => 'Coral', 'provider' => 'openai', 'language' => 'multi', 'role' => 'default', 'style' => 'Bright and approachable', 'priceMetric' => 'TTS: $15/1M chars'],
            ['voiceId' => 'echo', 'name' => 'Echo', 'provider' => 'openai', 'language' => 'multi', 'role' => 'default', 'style' => 'Crisp and direct', 'priceMetric' => 'TTS: $15/1M chars'],
            ['voiceId' => 'fable', 'name' => 'Fable', 'provider' => 'openai', 'language' => 'multi', 'role' => 'premium', 'style' => 'Characterful and clear', 'priceMetric' => 'TTS: $15/1M chars'],
            ['voiceId' => 'nova', 'name' => 'Nova', 'provider' => 'openai', 'language' => 'multi', 'role' => 'default', 'style' => 'Friendly and energetic', 'priceMetric' => 'TTS: $15/1M chars'],
            ['voiceId' => 'onyx', 'name' => 'Onyx', 'provider' => 'openai', 'language' => 'multi', 'role' => 'operator', 'style' => 'Deep and authoritative', 'priceMetric' => 'TTS: $15/1M chars'],
            ['voiceId' => 'sage', 'name' => 'Sage', 'provider' => 'openai', 'language' => 'multi', 'role' => 'default', 'style' => 'Calm and professional', 'priceMetric' => 'TTS: $15/1M chars'],
            ['voiceId' => 'shimmer', 'name' => 'Shimmer', 'provider' => 'openai', 'language' => 'multi', 'role' => 'default', 'style' => 'Soft and welcoming', 'priceMetric' => 'TTS: $15/1M chars'],
            ['voiceId' => 'verse', 'name' => 'Verse', 'provider' => 'openai', 'language' => 'multi', 'role' => 'premium', 'style' => 'Smooth and expressive', 'priceMetric' => 'TTS: $15/1M chars'],

            ['voiceId' => 'thalia', 'name' => 'Thalia', 'provider' => 'deepgram', 'language' => 'en-US', 'role' => 'default', 'style' => 'Clear, confident, energetic customer-service voice', 'priceMetric' => 'Aura-2: $0.030/1k chars', 'recommended' => true],
            ['voiceId' => 'andromeda', 'name' => 'Andromeda', 'provider' => 'deepgram', 'language' => 'en-US', 'role' => 'default', 'style' => 'Casual, expressive, comfortable IVR voice', 'priceMetric' => 'Aura-2: $0.030/1k chars', 'recommended' => true],
            ['voiceId' => 'helena', 'name' => 'Helena', 'provider' => 'deepgram', 'language' => 'en-US', 'role' => 'premium', 'style' => 'Caring, natural, friendly support voice', 'priceMetric' => 'Aura-2: $0.030/1k chars', 'recommended' => true],
            ['voiceId' => 'arcas', 'name' => 'Arcas', 'provider' => 'deepgram', 'language' => 'en-US', 'role' => 'operator', 'style' => 'Natural, smooth, clear operator voice', 'priceMetric' => 'Aura-2: $0.030/1k chars', 'recommended' => true],
            ['voiceId' => 'apollo', 'name' => 'Apollo', 'provider' => 'deepgram', 'language' => 'en-US', 'role' => 'operator', 'style' => 'Confident, comfortable, casual voice', 'priceMetric' => 'Aura-2: $0.030/1k chars', 'recommended' => true],
            ['voiceId' => 'aries', 'name' => 'Aries', 'provider' => 'deepgram', 'language' => 'en-US', 'role' => 'premium', 'style' => 'Warm, energetic, caring voice', 'priceMetric' => 'Aura-2: $0.030/1k chars', 'recommended' => true],
            ['voiceId' => 'alvaro', 'name' => 'Alvaro', 'provider' => 'deepgram', 'language' => 'es-ES', 'role' => 'default', 'style' => 'Calm, clear, knowledgeable Spanish voice', 'priceMetric' => 'Aura-2: $0.030/1k chars', 'recommended' => true],
            ['voiceId' => 'nestor', 'name' => 'Nestor', 'provider' => 'deepgram', 'language' => 'es-ES', 'role' => 'operator', 'style' => 'Calm, professional, clear Spanish voice', 'priceMetric' => 'Aura-2: $0.030/1k chars', 'recommended' => true],
            ['voiceId' => 'carina', 'name' => 'Carina', 'provider' => 'deepgram', 'language' => 'es-ES', 'role' => 'premium', 'style' => 'Professional, energetic Spanish IVR voice', 'priceMetric' => 'Aura-2: $0.030/1k chars', 'recommended' => true],
        ];

        foreach (self::languageDefinitions() as $code => $definition) {
            foreach (($definition['standard_voices'] ?? []) as $role => $profile) {
                if (! isset($profile['voiceId'])) {
                    continue;
                }

                $voices[] = [
                    'voiceId' => $profile['voiceId'],
                    'name' => $profile['name'] ?? $profile['voiceId'],
                    'provider' => $profile['provider'] ?? 'azure',
                    'language' => $code,
                    'role' => $role,
                    'style' => $profile['style'] ?? 'Localized neural voice',
                    'priceMetric' => $profile['priceMetric'] ?? '~$0.01/min voice',
                ];
            }
        }

        $voices = array_merge($voices, ElevenLabsVoiceCatalog::voices($elevenLabsVoiceIds));

        return collect($voices)
            ->unique(fn (array $voice) => implode('|', [
                $voice['provider'] ?? '',
                $voice['voiceId'] ?? '',
                $voice['language'] ?? '',
            ]))
            ->values()
            ->all();
    }

    public static function languageCodeForVoiceId(?string $voiceId, ?string $fallback = null): ?string
    {
        $voiceId = trim((string) $voiceId);

        if ($voiceId === '') {
            return self::normalizeLanguageCode($fallback);
        }

        $match = collect(self::voiceCatalog())->first(function (array $voice) use ($voiceId) {
            return strcasecmp((string) ($voice['voiceId'] ?? ''), $voiceId) === 0;
        });

        if ($match) {
            return self::normalizeLanguageCode($match['language'] ?? null, $fallback);
        }

        $prefixMatch = collect(array_keys(self::languageDefinitions()))
            ->first(fn (string $code) => str_starts_with($voiceId, $code . '-'));

        if ($prefixMatch) {
            return $prefixMatch;
        }

        return self::normalizeLanguageCode($fallback);
    }

    public static function phoneSetupOptions(?string $market): array
    {
        return match (self::normalizeMarket($market)) {
            self::UAE => [
                [
                    'value' => 'existing_business_number',
                    'label' => 'Forward my current number',
                    'description' => 'Keep your live line and forward it into tickIt with a local routing target.',
                    'recommended' => true,
                ],
                [
                    'value' => 'external_provider',
                    'label' => 'Import my current number',
                    'description' => 'Attach the UAE, German, US, or other carrier number you already own directly to this assistant.',
                    'recommended' => false,
                ],
                [
                    'value' => 'vapi_instant',
                    'label' => 'Create a test number',
                    'description' => 'Fastest for internal testing, but this creates a US-hosted number.',
                    'recommended' => false,
                ],
            ],
            default => [
                [
                    'value' => 'vapi_instant',
                    'label' => 'Create a new tickIt number',
                    'description' => 'Fastest for testing. We will provision a new US number for this assistant.',
                    'recommended' => true,
                ],
                [
                    'value' => 'external_provider',
                    'label' => 'Import my current number',
                    'description' => 'Bring over a US, German, or other carrier number and attach it directly to this assistant.',
                    'recommended' => false,
                ],
                [
                    'value' => 'existing_business_number',
                    'label' => 'Forward my current number',
                    'description' => 'Keep your live number and forward calls into a tickIt number when you are ready.',
                    'recommended' => false,
                ],
            ],
        };
    }

    public static function defaultPhoneSetupMode(?string $market): string
    {
        return match (self::normalizeMarket($market)) {
            self::UAE => 'existing_business_number',
            default => 'vapi_instant',
        };
    }

    public static function externalProviderOptions(?string $market = null): array
    {
        $market = self::normalizeMarket($market);

        $options = [
            [
                'value' => 'telnyx',
                'label' => 'Telnyx',
                'description' => 'Strong fit for local and international numbers, including UAE pilots.',
            ],
            [
                'value' => 'twilio',
                'label' => 'Twilio',
                'description' => 'Works well when your team already uses Twilio elsewhere.',
            ],
            [
                'value' => 'manual',
                'label' => 'Manual import',
                'description' => 'Use this if you already have the number and just need to save the routing plan.',
            ],
            [
                'value' => 'other',
                'label' => 'Other',
                'description' => 'Another carrier or SIP provider you want to bring into Vapi.',
            ],
        ];

        if ($market === self::UAE) {
            usort($options, function (array $left, array $right): int {
                $priority = ['telnyx' => 0, 'manual' => 1, 'other' => 2, 'twilio' => 3];

                return ($priority[$left['value']] ?? 10) <=> ($priority[$right['value']] ?? 10);
            });
        }

        return $options;
    }

    public static function existingNumberCountryOptions(?string $market = null): array
    {
        $options = [
            [
                'value' => 'us',
                'label' => 'US / Canada',
                'placeholder' => '+1 415 555 0123',
                'import_help' => 'Paste the exact North American number you want to import into Vapi for this assistant.',
                'forwarding_help' => 'Save the live North American number your team already answers so you can forward it when you are ready.',
                'provider_help' => 'Twilio and Telnyx both work well for North American imports.',
            ],
            [
                'value' => 'de',
                'label' => 'Germany',
                'placeholder' => '+49 30 1234567',
                'import_help' => 'Paste the German number you want to import in international format so Vapi can attach it cleanly.',
                'forwarding_help' => 'Save the German line you want to keep, then forward it into tickIt when you are ready to switch calls over.',
                'provider_help' => 'Telnyx is usually the cleanest path for German local numbers, but other BYO carriers also work.',
            ],
            [
                'value' => 'uae',
                'label' => 'UAE',
                'placeholder' => '+971 4 123 4567',
                'import_help' => 'Paste the UAE number you want to import in international format so the assistant can answer it directly.',
                'forwarding_help' => 'Save the UAE line you want to keep, then forward it into tickIt once the routing target is ready.',
                'provider_help' => 'Telnyx is usually the easiest carrier path for UAE-ready BYO imports.',
            ],
            [
                'value' => 'other',
                'label' => 'Other',
                'placeholder' => '+44 20 7946 0958',
                'import_help' => 'Paste the exact number in international format so we can save or import it correctly.',
                'forwarding_help' => 'Save the international line you want to keep so you can route it into tickIt later.',
                'provider_help' => 'Use the carrier that already owns the number, then connect it through your Vapi BYO credential.',
            ],
        ];

        $priority = self::normalizeMarket($market) === self::UAE
            ? ['uae', 'de', 'us', 'other']
            : ['us', 'de', 'uae', 'other'];

        usort($options, function (array $left, array $right) use ($priority): int {
            $leftIndex = array_search($left['value'], $priority, true);
            $rightIndex = array_search($right['value'], $priority, true);

            return ($leftIndex === false ? 999 : $leftIndex) <=> ($rightIndex === false ? 999 : $rightIndex);
        });

        return $options;
    }

    public static function inferExistingNumberCountry(?string $phoneNumber, ?string $market = null): string
    {
        $normalized = preg_replace('/[^0-9+]/', '', (string) $phoneNumber);

        if (str_starts_with($normalized, '+49')) {
            return 'de';
        }

        if (str_starts_with($normalized, '+971')) {
            return 'uae';
        }

        if (str_starts_with($normalized, '+1')) {
            return 'us';
        }

        return self::normalizeMarket($market) === self::UAE ? 'uae' : 'us';
    }

    public static function defaultExternalProvider(?string $market): string
    {
        return match (self::normalizeMarket($market)) {
            self::UAE => 'telnyx',
            default => 'manual',
        };
    }

    public static function stackFor(?string $market, ?string $languageCode): array
    {
        $market = self::normalizeMarket($market);
        $languageCode = self::normalizeLanguageCode($languageCode, self::defaultLanguageForMarket($market)) ?: self::defaultLanguageForMarket($market);
        $transcriber = self::transcriberProfile($languageCode);
        $standardVoice = self::standardVoiceProfile($languageCode, null, $market);
        $voiceSummary = $standardVoice
            ? (($standardVoice['name'] ?? $standardVoice['voiceId']) . ' via ' . ucfirst((string) ($standardVoice['provider'] ?? 'Azure')))
            : 'Vapi curated English voices, with Azure available for wider language coverage.';

        return match ($market) {
            self::UAE => [
                'market' => self::UAE,
                'title' => 'UAE-ready pilot stack',
                'telephony' => 'Use an existing UAE local number or provision one through Telnyx, then import it into Vapi.',
                'transcriber' => $transcriber['label'],
                'voice' => str_starts_with($languageCode, 'ar-')
                    ? 'Azure Neural Arabic voices, with English-ready fallback for bilingual teams'
                    : $voiceSummary,
                'llm' => 'Use the current OpenAI-backed assistant logic and switch models only when call quality requires it.',
                'note' => 'This keeps the stack global, but makes UAE local calling and Arabic intake a first-class option.',
            ],
            default => [
                'market' => self::GLOBAL,
                'title' => 'Global pilot stack',
                'telephony' => 'Use an instant tickIt US number for testing, or connect an external carrier for local markets.',
                'transcriber' => $transcriber['label'],
                'voice' => $voiceSummary,
                'llm' => 'Use the current OpenAI-backed assistant logic and upgrade models only when needed.',
                'note' => 'This keeps the setup quick now without blocking international rollout later.',
            ],
        };
    }

    public static function pilotPlaybook(?string $market, ?string $useCase, ?string $languageCode): array
    {
        $market = self::normalizeMarket($market);
        $useCase = trim((string) ($useCase ?: 'customer_support'));
        $languageCode = self::normalizeLanguageCode($languageCode, self::defaultLanguageForMarket($market)) ?: self::defaultLanguageForMarket($market);

        if ($market === self::UAE && $useCase === 'property_management') {
            return [
                'title' => 'UAE property-management pilot',
                'summary' => 'Start with one bilingual maintenance line, prove the intake quality, then expand once the team trusts the tickets and follow-up.',
                'recommended_preset' => 'Professional',
                'recommended_voice_path' => str_starts_with(strtolower($languageCode), 'ar-')
                    ? 'Azure Fatima or Hamdan for Arabic-first maintenance intake'
                    : 'Azure Sonia or Ryan for English-first maintenance intake',
                'recommended_number_path' => 'Use one UAE local number through Telnyx or your existing carrier before expanding the rollout.',
                'demo_calls' => [
                    'Active water leak in a tower apartment',
                    'AC outage during high heat hours',
                    'Building access or lockbox issue before a technician visit',
                ],
                'rollout_steps' => [
                    'Launch one property team or one after-hours maintenance line first.',
                    'Keep the assistant focused on intake, urgency, building context, and access notes.',
                    'Expand to broader resident operations only after the first pilot calls feel reliable.',
                ],
                'buyer_value' => [
                    'Arabic and English intake on the same maintenance line',
                    'Building, community, unit, and access context captured on the first call',
                    'Dispatch-ready tickets without a second follow-up call for missing details',
                ],
            ];
        }

        if ($useCase === 'property_management') {
            return [
                'title' => 'Property-management pilot',
                'summary' => 'Start with one maintenance workflow, measure how much manual call logging disappears, then expand to follow-up and scheduling.',
                'recommended_preset' => 'Professional',
                'recommended_voice_path' => 'Steady, clear maintenance intake with quick urgency triage',
                'recommended_number_path' => 'Use one live property-management line first so the pilot stays easy to measure.',
                'demo_calls' => [
                    'Leaking toilet or sink',
                    'No heat or no air conditioning',
                    'Lockout or building access problem',
                ],
                'rollout_steps' => [
                    'Start with one maintenance queue.',
                    'Confirm that urgent calls create cleaner tickets than the current manual flow.',
                    'Expand once the maintenance path is solid and the team trusts the follow-up.',
                ],
                'buyer_value' => [
                    'Missed maintenance calls turn into structured tickets',
                    'Urgent issues are flagged before dispatch waits on another call',
                    'Property, unit, and access details arrive with the ticket',
                ],
            ];
        }

        return [
            'title' => 'Pilot rollout',
            'summary' => 'Start with one workflow, keep the first pilot measurable, and expand after the team trusts the first calls.',
            'recommended_preset' => 'Professional',
            'recommended_voice_path' => 'Keep the voice path simple and aligned with the workspace language',
            'recommended_number_path' => 'Use one live number first so the rollout stays measurable.',
            'demo_calls' => [
                'One routine inbound request',
                'One urgent request that needs a clean handoff',
                'One follow-up or scheduling request',
            ],
            'rollout_steps' => [
                'Launch one workflow first.',
                'Review the first calls and refine the prompt.',
                'Expand once the handoff quality feels reliable.',
            ],
            'buyer_value' => [
                'Fewer missed calls',
                'Cleaner ticket creation',
                'Faster follow-up after each call',
            ],
        ];
    }

    public static function forWorkspace(Workspace $workspace, ?string $languageCode = null): array
    {
        $languageCode = $languageCode ?: $workspace->preferredLanguageCode();

        return self::stackFor($workspace->primaryMarket(), $languageCode);
    }

    public static function forWorkspacePlaybook(Workspace $workspace, ?string $languageCode = null): array
    {
        $languageCode = $languageCode ?: $workspace->preferredLanguageCode();

        return self::pilotPlaybook($workspace->primaryMarket(), $workspace->use_case, $languageCode);
    }

    protected static function orderedLanguageCodes(?string $market = null): array
    {
        $market = self::normalizeMarket($market);
        $priority = $market === self::UAE
            ? ['ar-AE', 'en-GB', 'en-US']
            : ['en-US', 'zh-CN', 'hi-IN', 'es-ES', 'fr-FR', 'ar-AE', 'bn-BD', 'pt-BR', 'ru-RU', 'ur-PK', 'id-ID', 'de-DE', 'sq-AL', 'ja-JP', 'ko-KR', 'fr-CA', 'en-GB'];

        $codes = array_keys(self::languageDefinitions());

        usort($codes, function (string $left, string $right) use ($priority): int {
            $leftIndex = array_search($left, $priority, true);
            $rightIndex = array_search($right, $priority, true);

            return ($leftIndex === false ? 999 : $leftIndex) <=> ($rightIndex === false ? 999 : $rightIndex);
        });

        return $codes;
    }

    protected static function languageAliasMap(): array
    {
        $map = [];

        foreach (self::languageDefinitions() as $code => $definition) {
            $map[strtolower($code)] = $code;

            foreach ($definition['aliases'] as $alias) {
                $map[strtolower($alias)] = $code;
            }
        }

        return $map;
    }

    protected static function languageDefinitions(): array
    {
        return [
            'en-US' => [
                'label' => 'English (US)',
                'aliases' => ['en', 'english', 'english us', 'english usa', 'american english', 'us english'],
                'transcriber' => [
                    'model' => 'nova-3-general',
                    'language' => 'en',
                    'label' => 'Deepgram Nova-3 (English)',
                    'fallback_language' => 'en-US',
                ],
            ],
            'en-GB' => [
                'label' => 'English (UK)',
                'aliases' => ['english uk', 'english gb', 'british english', 'uk english'],
                'transcriber' => [
                    'model' => 'nova-3-general',
                    'language' => 'en',
                    'label' => 'Deepgram Nova-3 (English)',
                    'fallback_language' => 'en-GB',
                ],
                'standard_voices' => [
                    'default' => ['provider' => 'azure', 'voiceId' => 'en-GB-SoniaNeural', 'name' => 'Sonia Neural', 'speed' => 1.0],
                    'operator' => ['provider' => 'azure', 'voiceId' => 'en-GB-RyanNeural', 'name' => 'Ryan Neural', 'speed' => 1.0],
                ],
            ],
            'ar-AE' => [
                'label' => 'Arabic',
                'aliases' => ['ar', 'arabic', 'arabic uae'],
                'transcriber' => [
                    'model' => 'nova-3',
                    'language' => 'multi',
                    'label' => 'Deepgram Nova-3 (multilingual Arabic-ready)',
                    'fallback_language' => 'ar-AE',
                ],
                'standard_voices' => [
                    'default' => ['provider' => 'azure', 'voiceId' => 'ar-AE-FatimaNeural', 'name' => 'Fatima Neural', 'speed' => 1.0],
                    'operator' => ['provider' => 'azure', 'voiceId' => 'ar-AE-HamdanNeural', 'name' => 'Hamdan Neural', 'speed' => 1.0],
                ],
            ],
            'es-ES' => [
                'label' => 'Spanish',
                'aliases' => ['es', 'spanish', 'spanish spain'],
                'transcriber' => [
                    'model' => 'nova-3',
                    'language' => 'es',
                    'label' => 'Deepgram Nova-3 (Spanish)',
                    'fallback_language' => 'es-ES',
                ],
                'standard_voices' => [
                    'default' => ['provider' => 'azure', 'voiceId' => 'es-ES-ElviraNeural', 'name' => 'Elvira Neural', 'speed' => 1.0],
                    'operator' => ['provider' => 'azure', 'voiceId' => 'es-ES-AlvaroNeural', 'name' => 'Alvaro Neural', 'speed' => 1.0],
                ],
            ],
            'fr-FR' => [
                'label' => 'French',
                'aliases' => ['fr', 'french', 'french france'],
                'transcriber' => [
                    'model' => 'nova-3',
                    'language' => 'fr',
                    'label' => 'Deepgram Nova-3 (French)',
                    'fallback_language' => 'fr-FR',
                ],
                'standard_voices' => [
                    'default' => ['provider' => 'azure', 'voiceId' => 'fr-FR-DeniseNeural', 'name' => 'Denise Neural', 'speed' => 1.0],
                    'operator' => ['provider' => 'azure', 'voiceId' => 'fr-FR-HenriNeural', 'name' => 'Henri Neural', 'speed' => 1.0],
                ],
            ],
            'fr-CA' => [
                'label' => 'French (Canada)',
                'aliases' => ['french canada', 'canadian french'],
                'transcriber' => [
                    'model' => 'nova-3',
                    'language' => 'fr-CA',
                    'label' => 'Deepgram Nova-3 (French Canada)',
                    'fallback_language' => 'fr-CA',
                ],
                'standard_voices' => [
                    'default' => ['provider' => 'azure', 'voiceId' => 'fr-CA-SylvieNeural', 'name' => 'Sylvie Neural', 'speed' => 1.0],
                    'operator' => ['provider' => 'azure', 'voiceId' => 'fr-CA-JeanNeural', 'name' => 'Jean Neural', 'speed' => 1.0],
                ],
            ],
            'de-DE' => [
                'label' => 'German',
                'aliases' => ['de', 'german', 'german germany'],
                'transcriber' => [
                    'model' => 'nova-3',
                    'language' => 'de',
                    'label' => 'Deepgram Nova-3 (German)',
                    'fallback_language' => 'de-DE',
                ],
                'standard_voices' => [
                    'default' => ['provider' => 'azure', 'voiceId' => 'de-DE-KlarissaNeural', 'name' => 'Klarissa Neural', 'speed' => 1.0],
                    'operator' => ['provider' => 'azure', 'voiceId' => 'de-DE-KlausNeural', 'name' => 'Klaus Neural', 'speed' => 0.98],
                ],
            ],
            'sq-AL' => [
                'label' => 'Albanian',
                'aliases' => ['sq', 'albanian', 'albanian albania', 'shqip', 'shqiperi', 'shqiperia'],
                'transcriber' => [
                    'provider' => 'gladia',
                    'model' => 'solaria-1',
                    'language' => 'sq',
                    'label' => 'Gladia Solaria-1 (Albanian, noise enhanced)',
                    'language_behaviour' => 'manual',
                    'audio_enhancer' => true,
                    'receive_partial_transcripts' => true,
                    'region' => 'eu-west',
                    'fallback' => [
                        'provider' => 'azure',
                        'language' => 'sq-AL',
                    ],
                ],
                'standard_voices' => [
                    'default' => ['provider' => 'azure', 'voiceId' => 'sq-AL-AnilaNeural', 'name' => 'Anila Neural', 'speed' => 1.0, 'style' => 'Warm Albanian front-desk voice'],
                    'operator' => ['provider' => 'azure', 'voiceId' => 'sq-AL-IlirNeural', 'name' => 'Ilir Neural', 'speed' => 1.0, 'style' => 'Clear Albanian operator voice'],
                ],
            ],
            'hi-IN' => [
                'label' => 'Hindi',
                'aliases' => ['hi', 'hindi', 'hindi india'],
                'transcriber' => [
                    'model' => 'nova-3',
                    'language' => 'hi',
                    'label' => 'Deepgram Nova-3 (Hindi)',
                    'fallback_language' => 'hi-IN',
                ],
                'standard_voices' => [
                    'default' => ['provider' => 'azure', 'voiceId' => 'hi-IN-SwaraNeural', 'name' => 'Swara Neural', 'speed' => 1.0],
                    'operator' => ['provider' => 'azure', 'voiceId' => 'hi-IN-MadhurNeural', 'name' => 'Madhur Neural', 'speed' => 1.0],
                ],
            ],
            'bn-BD' => [
                'label' => 'Bengali',
                'aliases' => ['bn', 'bangla', 'bengali', 'bengali bangladesh'],
                'transcriber' => [
                    'model' => 'nova-3',
                    'language' => 'bn',
                    'label' => 'Deepgram Nova-3 (Bengali)',
                    'fallback_language' => 'bn-BD',
                ],
                'standard_voices' => [
                    'default' => ['provider' => 'azure', 'voiceId' => 'bn-BD-NabanitaNeural', 'name' => 'Nabanita Neural', 'speed' => 1.0],
                    'operator' => ['provider' => 'azure', 'voiceId' => 'bn-BD-PradeepNeural', 'name' => 'Pradeep Neural', 'speed' => 1.0],
                ],
            ],
            'zh-CN' => [
                'label' => 'Chinese (Mandarin)',
                'aliases' => ['zh', 'zh-cn', 'mandarin', 'mandarin chinese', 'chinese', 'simplified chinese'],
                'transcriber' => [
                    'model' => 'nova-3',
                    'language' => 'zh',
                    'label' => 'Deepgram Nova-3 (Mandarin Chinese)',
                    'fallback_language' => 'zh-CN',
                ],
                'standard_voices' => [
                    'default' => ['provider' => 'azure', 'voiceId' => 'zh-CN-XiaoxiaoMultilingualNeural', 'name' => 'Xiaoxiao Multilingual Neural', 'speed' => 1.0],
                    'operator' => ['provider' => 'azure', 'voiceId' => 'zh-CN-YunfanMultilingualNeural', 'name' => 'Yunfan Multilingual Neural', 'speed' => 1.0],
                ],
            ],
            'pt-BR' => [
                'label' => 'Portuguese (Brazil)',
                'aliases' => ['pt', 'pt-br', 'portuguese', 'brazilian portuguese', 'portuguese brazil'],
                'transcriber' => [
                    'model' => 'nova-3',
                    'language' => 'pt-BR',
                    'label' => 'Deepgram Nova-3 (Portuguese)',
                    'fallback_language' => 'pt-BR',
                ],
                'standard_voices' => [
                    'default' => ['provider' => 'azure', 'voiceId' => 'pt-BR-FranciscaNeural', 'name' => 'Francisca Neural', 'speed' => 1.0],
                    'operator' => ['provider' => 'azure', 'voiceId' => 'pt-BR-AntonioNeural', 'name' => 'Antonio Neural', 'speed' => 1.0],
                ],
            ],
            'ru-RU' => [
                'label' => 'Russian',
                'aliases' => ['ru', 'russian'],
                'transcriber' => [
                    'model' => 'nova-3',
                    'language' => 'ru',
                    'label' => 'Deepgram Nova-3 (Russian)',
                    'fallback_language' => 'ru-RU',
                ],
                'standard_voices' => [
                    'default' => ['provider' => 'azure', 'voiceId' => 'ru-RU-DariyaNeural', 'name' => 'Dariya Neural', 'speed' => 1.0],
                    'operator' => ['provider' => 'azure', 'voiceId' => 'ru-RU-DmitryNeural', 'name' => 'Dmitry Neural', 'speed' => 1.0],
                ],
            ],
            'ur-PK' => [
                'label' => 'Urdu',
                'aliases' => ['ur', 'urdu', 'urdu pakistan'],
                'transcriber' => [
                    'model' => 'nova-3',
                    'language' => 'ur',
                    'label' => 'Deepgram Nova-3 (Urdu)',
                    'fallback_language' => 'ur-PK',
                ],
                'standard_voices' => [
                    'default' => ['provider' => 'azure', 'voiceId' => 'ur-PK-UzmaNeural', 'name' => 'Uzma Neural', 'speed' => 1.0],
                    'operator' => ['provider' => 'azure', 'voiceId' => 'ur-PK-AsadNeural', 'name' => 'Asad Neural', 'speed' => 1.0],
                ],
            ],
            'id-ID' => [
                'label' => 'Indonesian',
                'aliases' => ['id', 'bahasa', 'bahasa indonesia', 'indonesian'],
                'transcriber' => [
                    'model' => 'nova-3',
                    'language' => 'id',
                    'label' => 'Deepgram Nova-3 (Indonesian)',
                    'fallback_language' => 'id-ID',
                ],
                'standard_voices' => [
                    'default' => ['provider' => 'azure', 'voiceId' => 'id-ID-GadisNeural', 'name' => 'Gadis Neural', 'speed' => 1.0],
                    'operator' => ['provider' => 'azure', 'voiceId' => 'id-ID-ArdiNeural', 'name' => 'Ardi Neural', 'speed' => 1.0],
                ],
            ],
            'ja-JP' => [
                'label' => 'Japanese',
                'aliases' => ['ja', 'japanese'],
                'transcriber' => [
                    'model' => 'nova-3',
                    'language' => 'ja',
                    'label' => 'Deepgram Nova-3 (Japanese)',
                    'fallback_language' => 'ja-JP',
                ],
                'standard_voices' => [
                    'default' => ['provider' => 'azure', 'voiceId' => 'ja-JP-NanamiNeural', 'name' => 'Nanami Neural', 'speed' => 1.0],
                    'operator' => ['provider' => 'azure', 'voiceId' => 'ja-JP-KeitaNeural', 'name' => 'Keita Neural', 'speed' => 1.0],
                ],
            ],
            'ko-KR' => [
                'label' => 'Korean',
                'aliases' => ['ko', 'korean'],
                'transcriber' => [
                    'model' => 'nova-3',
                    'language' => 'ko',
                    'label' => 'Deepgram Nova-3 (Korean)',
                    'fallback_language' => 'ko-KR',
                ],
                'standard_voices' => [
                    'default' => ['provider' => 'azure', 'voiceId' => 'ko-KR-SunHiNeural', 'name' => 'SunHi Neural', 'speed' => 1.0],
                    'operator' => ['provider' => 'azure', 'voiceId' => 'ko-KR-InJoonNeural', 'name' => 'InJoon Neural', 'speed' => 1.0],
                ],
            ],
        ];
    }

    protected static function firstMessageCatalog(string $context): array
    {
        if ($context === 'maintenance') {
            return [
                'en-US' => 'Hi, thanks for calling maintenance. What issue can I help you report today?',
                'en-GB' => 'Hello, thanks for calling maintenance. What issue can I help you report today?',
                'ar-AE' => 'مرحباً، شكراً لاتصالك بفريق الصيانة. ما المشكلة التي يمكنني مساعدتك في الإبلاغ عنها اليوم؟',
                'es-ES' => 'Hola, gracias por llamar a mantenimiento. ¿Qué problema puedo ayudarle a reportar hoy?',
                'fr-FR' => "Bonjour, merci d'avoir appelé le service maintenance. Quel problème puis-je vous aider à signaler aujourd'hui ?",
                'fr-CA' => "Bonjour, merci d'avoir appelé le service de maintenance. Quel problème puis-je vous aider à signaler aujourd'hui ?",
                'de-DE' => 'Hallo, danke für Ihren Anruf bei der Instandhaltung. Wobei kann ich Ihnen heute helfen?',
                'sq-AL' => 'Përshëndetje, faleminderit që telefonuat mirëmbajtjen. Për cilin problem mund t’ju ndihmoj sot?',
                'hi-IN' => 'नमस्ते, मेंटेनेंस टीम को कॉल करने के लिए धन्यवाद। आज मैं किस समस्या को दर्ज करने में आपकी मदद करूँ?',
                'bn-BD' => 'হ্যালো, মেইনটেন্যান্স টিমে কল করার জন্য ধন্যবাদ। আজ কোন সমস্যাটি জানাতে আমি আপনাকে সাহায্য করতে পারি?',
                'zh-CN' => '您好，感谢致电维修服务。请问今天我可以帮您登记什么问题？',
                'pt-BR' => 'Olá, obrigado por ligar para a manutenção. Qual problema posso ajudar você a registrar hoje?',
                'ru-RU' => 'Здравствуйте, спасибо, что позвонили в службу обслуживания. С какой проблемой я могу помочь вам сегодня?',
                'ur-PK' => 'السلام علیکم، مینٹیننس ٹیم کو کال کرنے کا شکریہ۔ آج میں کون سا مسئلہ درج کرنے میں آپ کی مدد کروں؟',
                'id-ID' => 'Halo, terima kasih sudah menghubungi tim pemeliharaan. Masalah apa yang bisa saya bantu laporkan hari ini?',
                'ja-JP' => 'お電話ありがとうございます。メンテナンス窓口です。本日はどの不具合をお伺いしましょうか？',
                'ko-KR' => '안녕하세요, 유지보수팀에 전화 주셔서 감사합니다. 오늘 어떤 문제를 접수해 드릴까요?',
            ];
        }

        return [
            'en-US' => 'Hi, thanks for calling support. How can I help today?',
            'en-GB' => 'Hello, thanks for calling support. How can I help today?',
            'ar-AE' => 'مرحباً، شكراً لاتصالك بالدعم. كيف يمكنني مساعدتك اليوم؟',
            'es-ES' => 'Hola, gracias por llamar al soporte. ¿Cómo puedo ayudarle hoy?',
            'fr-FR' => "Bonjour, merci d'avoir appelé le support. Comment puis-je vous aider aujourd'hui ?",
            'fr-CA' => "Bonjour, merci d'avoir appelé le support. Comment puis-je vous aider aujourd'hui ?",
            'de-DE' => 'Hallo, danke für Ihren Anruf beim Support. Wie kann ich Ihnen heute helfen?',
            'sq-AL' => 'Përshëndetje, faleminderit që telefonuat mbështetjen. Si mund t’ju ndihmoj sot?',
            'hi-IN' => 'नमस्ते, सपोर्ट पर कॉल करने के लिए धन्यवाद। आज मैं आपकी कैसे मदद करूँ?',
            'bn-BD' => 'হ্যালো, সাপোর্টে কল করার জন্য ধন্যবাদ। আজ আমি কীভাবে সাহায্য করতে পারি?',
            'zh-CN' => '您好，感谢致电客服。请问今天我可以如何帮助您？',
            'pt-BR' => 'Olá, obrigado por ligar para o suporte. Como posso ajudar você hoje?',
            'ru-RU' => 'Здравствуйте, спасибо, что позвонили в поддержку. Чем я могу помочь вам сегодня?',
            'ur-PK' => 'السلام علیکم، سپورٹ پر کال کرنے کا شکریہ۔ آج میں آپ کی کیسے مدد کروں؟',
            'id-ID' => 'Halo, terima kasih sudah menghubungi dukungan. Bagaimana saya bisa membantu Anda hari ini?',
            'ja-JP' => 'お電話ありがとうございます。サポートです。本日はどのようにお手伝いできますか？',
            'ko-KR' => '안녕하세요, 지원팀에 전화 주셔서 감사합니다. 오늘 무엇을 도와드릴까요?',
        ];
    }

    protected static function shortContactName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        return $parts[0] ?? trim($name);
    }
}
