<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ElevenLabsVoiceCatalog
{
    public static function voices(): array
    {
        if (app()->runningUnitTests()) {
            return self::fallbackVoices();
        }

        $apiKey = trim((string) config('services.elevenlabs.key'));

        if ($apiKey === '') {
            return self::fallbackVoices();
        }

        try {
            return Cache::remember(
                'elevenlabs.voice_catalog.'.sha1($apiKey),
                now()->addHour(),
                fn () => self::fetchVoices($apiKey),
            );
        } catch (\Throwable) {
            return self::fetchVoices($apiKey);
        }
    }

    private static function fetchVoices(string $apiKey): array
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

            return $voices !== [] ? $voices : self::fallbackVoices();
        } catch (\Throwable) {
            return self::fallbackVoices();
        }
    }

    public static function fallbackVoices(): array
    {
        return [
            self::voice('EXAVITQu4vr4xnSDxMaL', 'Sarah', 'Mature, reassuring, confident support voice', 'premium'),
            self::voice('CwhRBWXzGAHq8TQ4Fs17', 'Roger', 'Laid-back, casual, resonant operator voice', 'operator'),
            self::voice('JBFqnCBsd6RMkjVDRZzb', 'George', 'Warm, grounded storyteller voice', 'operator'),
            self::voice('hpp4J3VqNfWAUOO0d1Us', 'Bella', 'Warm, bright, professional front-desk voice', 'default'),
            self::voice('SAz9YHcvj6GT2YYXdXww', 'River', 'Relaxed, neutral, informative voice', 'default'),
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
        $accent = trim((string) ($labels['accent'] ?? ''));
        $category = trim((string) ($voice['category'] ?? ''));
        $styleParts = array_filter([
            $description !== '' ? $description : null,
            $accent !== '' ? 'Accent: '.$accent : null,
            $category !== '' ? 'Category: '.$category : null,
        ]);

        return self::voice(
            $id,
            self::cleanName($name),
            mb_substr(implode(' ', $styleParts) ?: 'Natural ElevenLabs voice from your connected account.', 0, 180),
            self::roleForVoice($name, $description, $gender),
        );
    }

    private static function voice(string $voiceId, string $name, string $style, string $role): array
    {
        return [
            'voiceId' => $voiceId,
            'name' => $name,
            'provider' => '11labs',
            'language' => 'multi',
            'role' => $role,
            'style' => $style,
            'priceMetric' => 'Flash: $0.05/1k chars',
            'priceDetail' => 'Uses your ElevenLabs character quota. Free tier includes limited monthly credits; upgrade ElevenLabs before production volume.',
            'recommended' => in_array($role, ['default', 'operator', 'premium'], true),
            'model' => 'eleven_flash_v2_5',
        ];
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
