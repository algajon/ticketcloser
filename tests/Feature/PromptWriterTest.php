<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\PromptVersion;
use App\Models\Workspace;
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
        $this->workspace = Workspace::factory()->create([
            'onboarding_step' => 'done',
            'plan_key' => 'free',
            'use_case' => 'customer_support',
            'use_case_details' => 'We handle inbound support calls for a SaaS business and often need to confirm account context before follow-up.',
        ]);

        \DB::table('workspace_memberships')->insert([
            'user_id' => $this->user->id,
            'workspace_id' => $this->workspace->id,
            'role' => 'owner',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->user)
            ->withSession(['current_workspace_id' => $this->workspace->id]);
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
            'description' => 'Handle premium eyewear appointment requests and suggest bookings when it helps.',
            'assistant_name' => 'Eyewear Concierge',
            'first_message' => 'Thanks for calling Northline Eyewear. How can I help today?',
            'assistant_type' => 'premium_concierge',
            'tone' => 'friendly',
            'strictness' => 'high',
            'tools_enabled' => ['create_ticket', 'book_meeting'],
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('markdown', $data);
        $this->assertSame('template', $data['mode']);
        $this->assertFalse($data['ai_available']);
        $this->assertStringContainsString('Business Context', $data['markdown']);
        $this->assertStringContainsString($this->workspace->name, $data['markdown']);
        $this->assertStringContainsString('Role & Goal', $data['markdown']);
        $this->assertStringContainsString('Safety', $data['markdown']);
        $this->assertStringContainsString('log the request first', $data['markdown']);
        $this->assertDatabaseHas('prompt_versions', ['assistant_type' => 'premium_concierge']);

        $version = PromptVersion::query()->latest('id')->first();
        $this->assertNotNull($version);
        $this->assertStringContainsString('General customer support', $version->input_summary);
        $this->assertStringContainsString('SaaS business', $version->input_summary);
        $this->assertStringContainsString('Opening line: Thanks for calling Northline Eyewear. How can I help today?', $version->input_summary);
        $this->assertStringContainsString('Preferred opening line', $data['markdown']);
    }

    /** @test */
    public function prompt_version_is_saved_with_correct_workspace(): void
    {
        config(['services.openai.api_key' => null]);

        $this->postJson(route('app.prompt-writer.generate'), [
            'description' => 'General intake assistant.',
            'assistant_type' => 'bright_guide',
            'tone' => 'professional',
            'strictness' => 'medium',
        ]);

        $this->assertDatabaseHas('prompt_versions', [
            'workspace_id' => $this->workspace->id,
            'assistant_type' => 'bright_guide',
        ]);
    }

    /** @test */
    public function prompt_generation_accepts_longer_assistant_context_from_the_form(): void
    {
        config(['services.openai.api_key' => null]);

        $longDescription = 'Assistant context: ' . str_repeat(
            'Collect the caller details, confirm the request, create the ticket first, then move into booking when follow-up is needed. ',
            16
        );

        $response = $this->postJson(route('app.prompt-writer.generate'), [
            'description' => $longDescription,
            'assistant_name' => 'Operations Desk',
            'assistant_type' => 'steady_operator',
            'tone' => 'professional',
            'strictness' => 'medium',
            'tools_enabled' => ['create_ticket', 'book_meeting'],
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['markdown', 'mode', 'ai_available']);
        $this->assertDatabaseHas('prompt_versions', [
            'workspace_id' => $this->workspace->id,
            'assistant_type' => 'steady_operator',
        ]);
    }
}
