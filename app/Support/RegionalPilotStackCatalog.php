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
        $market = self::normalizeMarket($market);

        $options = [
            ['value' => 'en-US', 'label' => 'English (US)'],
            ['value' => 'en-GB', 'label' => 'English (UK)'],
            ['value' => 'ar-AE', 'label' => 'Arabic (UAE)'],
            ['value' => 'es-ES', 'label' => 'Spanish'],
            ['value' => 'fr-FR', 'label' => 'French'],
            ['value' => 'fr-CA', 'label' => 'French (Canada)'],
        ];

        if ($market === self::UAE) {
            usort($options, function (array $left, array $right): int {
                $priority = ['ar-AE' => 0, 'en-GB' => 1, 'en-US' => 2];

                return ($priority[$left['value']] ?? 10) <=> ($priority[$right['value']] ?? 10);
            });
        }

        return $options;
    }

    public static function defaultLanguageForMarket(?string $market, ?string $fallback = 'en-US'): string
    {
        return match (self::normalizeMarket($market)) {
            self::UAE => 'ar-AE',
            default => trim((string) ($fallback ?: 'en-US')) ?: 'en-US',
        };
    }

    public static function normalizeLanguageCode(?string $languageCode, ?string $fallback = null): ?string
    {
        $languageCode = strtolower(trim((string) $languageCode));
        $fallback = trim((string) $fallback);

        if ($languageCode === '') {
            return $fallback !== '' ? self::normalizeLanguageCode($fallback) : null;
        }

        $aliases = [
            'en' => 'en-US',
            'en-us' => 'en-US',
            'en-gb' => 'en-GB',
            'english' => 'en-US',
            'english us' => 'en-US',
            'english uk' => 'en-GB',
            'ar' => 'ar-AE',
            'ar-ae' => 'ar-AE',
            'arabic' => 'ar-AE',
            'arabic uae' => 'ar-AE',
            'es' => 'es-ES',
            'es-es' => 'es-ES',
            'spanish' => 'es-ES',
            'fr' => 'fr-FR',
            'fr-fr' => 'fr-FR',
            'fr-ca' => 'fr-CA',
            'french' => 'fr-FR',
            'french canada' => 'fr-CA',
            'multi' => 'multi',
            'multilingual' => 'multi',
        ];

        if (array_key_exists($languageCode, $aliases)) {
            return $aliases[$languageCode];
        }

        foreach (self::languageOptions() as $option) {
            if (strtolower($option['value']) === $languageCode) {
                return $option['value'];
            }
        }

        $primary = explode('-', $languageCode)[0] ?? $languageCode;

        if (array_key_exists($primary, $aliases)) {
            return $aliases[$primary];
        }

        if ($fallback !== '') {
            return self::normalizeLanguageCode($fallback);
        }

        return strtoupper($primary) === 'MULTI' ? 'multi' : null;
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

        foreach (self::languageOptions() as $option) {
            if ($option['value'] === $languageCode) {
                return $option['label'];
            }
        }

        return strtoupper(str_replace('-', ' ', $languageCode));
    }

    public static function transcriberProfile(?string $languageCode): array
    {
        $languageCode = trim((string) $languageCode);

        return match (true) {
            str_starts_with($languageCode, 'ar-AE') => [
                'provider' => 'deepgram',
                'model' => 'nova-3',
                // Vapi's current nova-3 validation rejects ar-AE directly, so use the
                // multilingual route while keeping Arabic voice/fallback behavior.
                'language' => 'multi',
                'label' => 'Deepgram Nova-3 (multilingual Arabic-ready)',
                'fallback' => ['provider' => 'azure', 'language' => 'ar-AE'],
            ],
            str_starts_with($languageCode, 'fr-CA') => [
                'provider' => 'deepgram',
                'model' => 'nova-3',
                'language' => 'fr',
                'label' => 'Deepgram Nova-3 (French)',
                'fallback' => ['provider' => 'azure', 'language' => 'fr-CA'],
            ],
            str_starts_with($languageCode, 'fr-') => [
                'provider' => 'deepgram',
                'model' => 'nova-3',
                'language' => 'fr',
                'label' => 'Deepgram Nova-3 (French)',
                'fallback' => ['provider' => 'azure', 'language' => $languageCode],
            ],
            str_starts_with($languageCode, 'es-') => [
                'provider' => 'deepgram',
                'model' => 'nova-3',
                'language' => 'es',
                'label' => 'Deepgram Nova-3 (Spanish)',
                'fallback' => ['provider' => 'azure', 'language' => $languageCode],
            ],
            default => [
                'provider' => 'deepgram',
                'model' => 'nova-3-general',
                'language' => 'en',
                'label' => 'Deepgram Nova-3 (English)',
                'fallback' => ['provider' => 'azure', 'language' => $languageCode ?: 'en-US'],
            ],
        };
    }

    public static function phoneSetupOptions(?string $market): array
    {
        return match (self::normalizeMarket($market)) {
            self::UAE => [
                [
                    'value' => 'existing_business_number',
                    'label' => 'Existing local number',
                    'description' => 'Forward or import the UAE number your team already uses.',
                    'recommended' => true,
                ],
                [
                    'value' => 'external_provider',
                    'label' => 'External provider',
                    'description' => 'Provision a local UAE number through Telnyx or another carrier, then import it.',
                    'recommended' => false,
                ],
                [
                    'value' => 'vapi_instant',
                    'label' => 'Instant test number',
                    'description' => 'Fastest for internal testing, but this creates a US-hosted number.',
                    'recommended' => false,
                ],
            ],
            default => [
                [
                    'value' => 'vapi_instant',
                    'label' => 'Instant tickIt number',
                    'description' => 'Create a US-hosted number right away for testing and launch.',
                    'recommended' => true,
                ],
                [
                    'value' => 'existing_business_number',
                    'label' => 'Existing business number',
                    'description' => 'Keep your current number and save the routing details you want to use.',
                    'recommended' => false,
                ],
                [
                    'value' => 'external_provider',
                    'label' => 'External provider',
                    'description' => 'Use Telnyx or another carrier for local or international numbers.',
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
        $languageCode = trim((string) ($languageCode ?: self::defaultLanguageForMarket($market)));
        $transcriber = self::transcriberProfile($languageCode);

        return match ($market) {
            self::UAE => [
                'market' => self::UAE,
                'title' => 'UAE-ready pilot stack',
                'telephony' => 'Use an existing UAE local number or provision one through Telnyx, then import it into Vapi.',
                'transcriber' => $transcriber['label'],
                'voice' => str_starts_with($languageCode, 'ar-')
                    ? 'Azure Neural Arabic voices, with English-ready fallback for bilingual teams'
                    : 'Azure Neural English voices, with Arabic-ready transcription available',
                'llm' => 'Use the current OpenAI-backed assistant logic and switch models only when call quality requires it.',
                'note' => 'This keeps the stack global, but makes UAE local calling and Arabic intake a first-class option.',
            ],
            default => [
                'market' => self::GLOBAL,
                'title' => 'Global pilot stack',
                'telephony' => 'Use an instant tickIt US number for testing, or connect an external carrier for local markets.',
                'transcriber' => $transcriber['label'],
                'voice' => 'Vapi voices for standard English, with Azure available for supported multilingual routes.',
                'llm' => 'Use the current OpenAI-backed assistant logic and upgrade models only when needed.',
                'note' => 'This keeps the setup quick now without blocking international rollout later.',
            ],
        };
    }

    public static function pilotPlaybook(?string $market, ?string $useCase, ?string $languageCode): array
    {
        $market = self::normalizeMarket($market);
        $useCase = trim((string) ($useCase ?: 'customer_support'));
        $languageCode = trim((string) ($languageCode ?: self::defaultLanguageForMarket($market)));

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
                    'No heat or AC issue',
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
}
