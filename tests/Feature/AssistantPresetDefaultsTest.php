<?php

namespace Tests\Feature;

use App\Models\AssistantPreset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssistantPresetDefaultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_presets_are_seeded_including_custom(): void
    {
        $presets = AssistantPreset::ensureDefaults();

        $this->assertGreaterThanOrEqual(5, $presets->count());
        $this->assertSame('bright_guide', $presets->first()->key);
        $this->assertTrue($presets->contains(fn (AssistantPreset $preset) => $preset->key === 'custom'));
        $this->assertDatabaseHas('assistant_presets', ['key' => 'confident_closer']);
        $this->assertDatabaseHas('assistant_presets', ['key' => 'premium_concierge']);
    }
}
