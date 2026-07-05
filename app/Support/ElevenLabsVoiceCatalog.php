<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ElevenLabsVoiceCatalog
{
    public static function voices(array $voiceIds = []): array
    {
        $voiceIds = collect($voiceIds)
            ->map(fn ($voiceId) => trim((string) $voiceId))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (app()->runningUnitTests()) {
            return self::fallbackVoices();
        }

        $apiKey = trim((string) config('services.elevenlabs.key'));

        if ($apiKey === '') {
            return self::fallbackVoices();
        }

        try {
            return Cache::remember(
                'elevenlabs.voice_catalog.v2.'.sha1($apiKey.'|'.implode(',', $voiceIds)),
                now()->addHour(),
                fn () => self::fetchVoices($apiKey, $voiceIds),
            );
        } catch (\Throwable) {
            return self::fetchVoices($apiKey, $voiceIds);
        }
    }

    private static function fetchVoices(string $apiKey, array $voiceIds = []): array
    {
        try {
            $baseUrl = rtrim((string) config('services.elevenlabs.base_url', 'https://api.elevenlabs.io'), '/');
            $response = Http::baseUrl($baseUrl)
                ->withHeaders(['xi-api-key' => $apiKey])
                ->acceptJson()
                ->timeout(8)
                ->get('/v2/voices', ['page_size' => 30])
                ->throw()
                ->json();

            $voices = collect($response['voices'] ?? [])
                ->filter(fn ($voice) => is_array($voice) && filled($voice['voice_id'] ?? null))
                ->map(fn (array $voice) => self::voiceFromApi($voice))
                ->filter()
                ->values()
                ->all();

            $knownVoiceIds = collect($voices)
                ->pluck('voiceId')
                ->filter()
                ->map(fn ($voiceId) => strtolower((string) $voiceId))
                ->all();

            foreach ($voiceIds as $voiceId) {
                if (in_array(strtolower((string) $voiceId), $knownVoiceIds, true)) {
                    continue;
                }

                $voice = self::fetchVoiceById($baseUrl, $apiKey, $voiceId);

                if ($voice) {
                    $voices[] = $voice;
                    $knownVoiceIds[] = strtolower((string) $voice['voiceId']);
                }
            }

            return $voices !== [] ? $voices : self::fallbackVoices();
        } catch (\Throwable) {
            return self::fallbackVoices();
        }
    }

    private static function fetchVoiceById(string $baseUrl, string $apiKey, string $voiceId): ?array
    {
        try {
            $response = Http::baseUrl($baseUrl)
                ->withHeaders(['xi-api-key' => $apiKey])
                ->acceptJson()
                ->timeout(8)
                ->get('/v1/voices/'.rawurlencode($voiceId))
                ->throw()
                ->json();

            return is_array($response) ? self::voiceFromApi($response) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public static function fallbackVoices(): array
    {
        return [
            self::voice('EXAVITQu4vr4xnSDxMaL', 'Sarah', 'Mature, reassuring, confident support voice', 'premium', 'American', 'female'),
            self::voice('CwhRBWXzGAHq8TQ4Fs17', 'Roger', 'Laid-back, casual, resonant operator voice', 'operator', 'American', 'male'),
            self::voice('JBFqnCBsd6RMkjVDRZzb', 'George', 'Warm, grounded storyteller voice', 'operator', 'British', 'male'),
            self::voice('hpp4J3VqNfWAUOO0d1Us', 'Bella', 'Warm, bright, professional front-desk voice', 'default', 'American', 'female'),
            self::voice('SAz9YHcvj6GT2YYXdXww', 'River - Relaxed, Neutral, Informative', 'Relaxed, neutral, informative conversational voice', 'default', 'American', 'neutral'),
            self::voice('cjVigY5qzO86Huf0OWal', 'Eric - Smooth, Trustworthy', 'Smooth, trustworthy conversational voice', 'operator', 'American', 'male'),
        ];
    }

    private static function voiceFromApi(array $voice): ?array
    {
        $id = trim((string) ($voice['voice_id'] ?? ''));
        $name = trim((string) ($voice['name'] ?? $id));

        if ($id === '' || $name === '') {
            return null;
        }

        $description = trim((string) ($voice['description'] ?? ''));
        $labels = is_array($voice['labels'] ?? null) ? $voice['labels'] : [];
        $gender = strtolower((string) ($labels['gender'] ?? ''));
        $accent = self::label((string) ($labels['accent'] ?? ''));
        $age = self::label((string) ($labels['age'] ?? ''));
        $useCase = self::label((string) ($labels['use_case'] ?? ''));
        $descriptive = self::label((string) ($labels['descriptive'] ?? ''));
        $category = trim((string) ($voice['category'] ?? ''));
        $verifiedLanguages = collect($voice['verified_languages'] ?? [])
            ->filter(fn ($language) => is_array($language))
            ->map(fn (array $language) => [
                'language' => trim((string) ($language['language'] ?? '')),
                'locale' => trim((string) ($language['locale'] ?? '')),
                'accent' => self::label((string) ($language['accent'] ?? '')),
                'previewUrl' => trim((string) ($language['preview_url'] ?? '')),
            ])
            ->filter(fn (array $language) => filled($language['locale']) || filled($language['language']))
            ->values()
            ->all();
        $supportedLanguages = collect($verifiedLanguages)
            ->map(fn (array $language) => $language['locale'] ?: $language['language'])
            ->filter()
            ->unique()
            ->values()
            ->all();
        $language = count($supportedLanguages) === 1 ? $supportedLanguages[0] : 'multi';
        $previewUrl = trim((string) ($voice['preview_url'] ?? ''))
            ?: (string) collect($verifiedLanguages)->pluck('previewUrl')->filter()->first();
        $styleParts = array_filter([
            $description !== '' ? $description : null,
            $accent !== '' ? 'Accent: '.$accent : null,
            $gender !== '' ? 'Voice: '.self::label($gender) : null,
            $age !== '' ? 'Age: '.$age : null,
            $useCase !== '' ? 'Use case: '.$useCase : null,
            $descriptive !== '' ? 'Tone: '.$descriptive : null,
            $category !== '' ? 'Category: '.$category : null,
        ]);

        return self::voice(
            $id,
            self::cleanName($name),
            mb_substr(implode(' ', $styleParts) ?: 'Natural ElevenLabs voice from your connected account.', 0, 180),
            self::roleForVoice($name, $description, $gender),
            $accent,
            self::label($gender),
            $previewUrl,
            $language,
            $supportedLanguages,
            $description,
            $category,
        );
    }

    private static function voice(
        string $voiceId,
        string $name,
        string $style,
        string $role,
        string $accent = '',
        string $gender = '',
        string $previewUrl = '',
        string $language = 'multi',
        array $supportedLanguages = [],
        string $description = '',
        string $category = '',
    ): array
    {
        return [
            'voiceId' => $voiceId,
            'name' => $name,
            'provider' => '11labs',
            'language' => $language ?: 'multi',
            'supportedLanguages' => $supportedLanguages,
            'accent' => $accent,
            'gender' => $gender,
            'description' => $description,
            'category' => $category,
            'previewUrl' => $previewUrl,
            'role' => $role,
            'style' => $style,
            'priceMetric' => 'Flash: $0.05/1k chars',
            'priceDetail' => 'Live calls use your ElevenLabs character quota. Voice samples use ElevenLabs hosted previews and do not generate new speech.',
            'recommended' => in_array($role, ['default', 'operator', 'premium'], true),
            'model' => 'eleven_flash_v2_5',
        ];
    }

    private static function label(string $value): string
    {
        $value = trim(str_replace(['_', '-'], ' ', $value));

        if ($value === '') {
            return '';
        }

        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    private static function cleanName(string $name): string
    {
        $name = preg_replace('/\s+/', ' ', trim($name));

        return mb_substr((string) $name, 0, 72);
    }

    private static function roleForVoice(string $name, string $description, string $gender): string
    {
        $haystack = strtolower($name.' '.$description.' '.$gender);

        if (str_contains($haystack, 'confident') || str_contains($haystack, 'deep') || str_contains($haystack, 'resonant')) {
            return 'operator';
        }

        if (str_contains($haystack, 'warm') || str_contains($haystack, 'reassuring') || str_contains($haystack, 'professional')) {
            return 'premium';
        }

        return 'default';
    }
}
