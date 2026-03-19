<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use App\Services\PromptGenerationService;
use App\Models\PromptVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromptWriterTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->create();
        \DB::table('workspace_memberships')->insert([
            'user_id' => $this->user->id,
            'workspace_id' => $this->workspace->id,
            'role' => 'owner',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        session(['current_workspace_id' => $this->workspace->id]);
        $this->actingAs($this->user);
    }

    /** @test */
    public function prompt_writer_page_is_accessible(): void
    {
        $response = $this->get(route('app.prompt-writer.index'));
        $response->assertStatus(200);
        $response->assertViewIs('prompt-writer.index');
    }

    /** @test */
    public function prompt_generation_uses_template_fallback_when_no_llm_key(): void
    {
        // Ensure no API key configured
        config(['services.openai.api_key' => null]);

        $response = $this->postJson(route('app.prompt-writer.generate'), [
            'description' => 'Mortgage intake assistant.',
            'assistant_type' => 'mortgage',
            'tone' => 'friendly',
            'strictness' => 'high',
            'tools_enabled' => [],
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('markdown', $data);
        $this->assertStringContainsString('Role & Goal', $data['markdown']);
        $this->assertStringContainsString('Safety', $data['markdown']);
        $this->assertDatabaseHas('prompt_versions', ['assistant_type' => 'mortgage']);
    }

    /** @test */
    public function prompt_version_is_saved_with_correct_workspace(): void
    {
        config(['services.openai.api_key' => null]);

        $this->postJson(route('app.prompt-writer.generate'), [
            'description' => 'Support agent.',
            'assistant_type' => 'support',
            'tone' => 'professional',
            'strictness' => 'medium',
        ]);

        $this->assertDatabaseHas('prompt_versions', [
            'workspace_id' => $this->workspace->id,
            'assistant_type' => 'support',
        ]);
    }
}
