<?php

namespace Tests\Unit;

use App\Support\ElevenLabsVoiceCatalog;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ElevenLabsVoiceCatalogTest extends TestCase
{
    public function test_elevenlabs_api_voice_metadata_is_mapped_for_friendly_picker_labels(): void
    {
        $voice = $this->mapVoiceFromApi([
            'voice_id' => 'cjVigY5qzO86Huf0OWal',
            'name' => 'Eric - Smooth, Trustworthy',
            'category' => 'professional',
            'description' => 'Smooth and reliable conversational voice.',
            'preview_url' => 'https://example.test/eric.mp3',
            'labels' => [
                'accent' => 'american',
                'gender' => 'male',
                'use_case' => 'conversational',
                'age' => 'middle_aged',
                'descriptive' => 'classy',
            ],
            'verified_languages' => [
                [
                    'language' => 'en',
                    'locale' => 'en-US',
                    'accent' => 'american',
                    'preview_url' => 'https://example.test/eric-us.mp3',
                ],
            ],
        ]);

        $this->assertSame('cjVigY5qzO86Huf0OWal', $voice['voiceId']);
        $this->assertSame('Eric - Smooth, Trustworthy', $voice['name']);
        $this->assertSame('American', $voice['accent']);
        $this->assertSame('Male', $voice['gender']);
        $this->assertSame('en-US', $voice['language']);
        $this->assertSame(['en-US'], $voice['supportedLanguages']);
        $this->assertSame('https://example.test/eric.mp3', $voice['previewUrl']);
        $this->assertStringContainsString('Accent: American', $voice['style']);
        $this->assertStringContainsString('Voice: Male', $voice['style']);
    }

    private function mapVoiceFromApi(array $payload): array
    {
        $reflection = new ReflectionClass(ElevenLabsVoiceCatalog::class);
        $method = $reflection->getMethod('voiceFromApi');
        $method->setAccessible(true);

        return $method->invoke(null, $payload);
    }
}
