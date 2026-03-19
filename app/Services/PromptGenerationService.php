<?php

namespace App\Services;

use App\Models\PromptTemplate;
use App\Models\PromptVersion;
use App\Models\Workspace;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PromptGenerationService
{
    /**
     * Generate a structured voice assistant system prompt.
     */
    public function generate(array $input, Workspace $workspace, int $userId): PromptVersion
    {
        $assistantType = $input['assistant_type'] ?? 'support';
        $tone = $input['tone'] ?? 'professional';
        $strictness = $input['strictness'] ?? 'medium';
        $tools = $input['tools_enabled'] ?? [];
        $summary = $input['description'] ?? '';

        Log::info('PromptGenerationService: generating', [
            'workspace_id' => $workspace->id,
            'assistant_type' => $assistantType,
            'user_id' => $userId,
        ]);

        // Build the prompt
        $systemPrompt = $this->buildPrompt($assistantType, $tone, $strictness, $tools, $summary);

        // Save and return
        return PromptVersion::create([
            'workspace_id' => $workspace->id,
            'created_by' => $userId,
            'assistant_type' => $assistantType,
            'tone' => $tone,
            'strictness' => $strictness,
            'tools_enabled' => $tools,
            'input_summary' => $summary,
            'output_markdown' => $systemPrompt,
        ]);
    }

    /**
     * Build structural prompt using an LLM provider if configured,
     * falling back to a rule-based template builder.
     */
    protected function buildPrompt(
        string $assistantType,
        string $tone,
        string $strictness,
        array $tools,
        string $description
    ): string {
        // Try LLM if configured
        $apiKey = config('services.openai.api_key');
        $baseUrl = config('services.openai.base_url', 'https://api.openai.com/v1');
        $model = config('services.openai.model', 'gpt-4o-mini');

        if ($apiKey) {
            try {
                return $this->callLlm($apiKey, $baseUrl, $model, $assistantType, $tone, $strictness, $tools, $description);
            } catch (\Exception $e) {
                Log::warning('PromptGenerationService: LLM call failed, using template', ['error' => $e->getMessage()]);
            }
        }

        // Template fallback
        return $this->templateFallback($assistantType, $tone, $strictness, $tools, $description);
    }

    protected function callLlm(
        string $apiKey,
        string $baseUrl,
        string $model,
        string $assistantType,
        string $tone,
        string $strictness,
        array $tools,
        string $description
    ): string {
        $toolList = implode(', ', $tools) ?: 'none';

        $meta = <<<PROMPT
You are an expert voice assistant prompt engineer. Generate a STRUCTURED, DETAILED system prompt (in Markdown) for an AI voice assistant.

Description: {$description}
Type: {$assistantType}
Tone: {$tone}
Strictness: {$strictness}
Tools enabled: {$toolList}

The prompt MUST include these Markdown sections:
## Role & Goal
## Allowed Tools & When to Use
## Required Fields to Collect
## Conversation Rules
## Safety & Privacy
## Escalation & Handoff
## Examples (short, 1-2)

Keep it concise but comprehensive. Write ONLY the prompt, no meta-commentary.
PROMPT;

        $response = Http::withToken($apiKey)
            ->baseUrl($baseUrl)
            ->timeout(30)
            ->post('/chat/completions', [
                'model' => $model,
                'messages' => [['role' => 'user', 'content' => $meta]],
                'max_tokens' => 1024,
            ]);

        return $response->json('choices.0.message.content', '');
    }

    protected function templateFallback(
        string $assistantType,
        string $tone,
        string $strictness,
        array $tools,
        string $description
    ): string {
        $toolList = implode(', ', $tools) ?: '_none_';
        $typeLabel = ucfirst($assistantType);
        $strictnessNote = match ($strictness) {
            'high' => 'Never deviate from the script. If the caller goes off-topic, politely redirect.',
            'low' => 'Be flexible. Use judgement to help callers even if slightly off-topic.',
            default => 'Stick to the main task but use reasonable judgement for minor deviations.',
        };
        $toneNote = match ($tone) {
            'friendly' => 'Warm, empathetic, first-name basis.',
            'strict' => 'Formal, brief, no small talk.',
            default => 'Professional and courteous.',
        };

        return <<<MARKDOWN
# {$typeLabel} AI Voice Assistant — System Prompt

> **Description:** {$description}

---

## Role & Goal

You are a {$tone} AI voice assistant for a {$typeLabel} team. Your job is to help callers log a {$assistantType} request quickly and accurately. Tone: {$toneNote}

---

## Allowed Tools & When to Use

**Tools enabled:** {$toolList}

- Use `create_ticket` immediately after collecting all required fields.
- Do not use any tool not listed above.

---

## Required Fields to Collect

1. **Full name** — Required before anything else.
2. **Contact number or email** — For follow-up.
3. **Issue description** — What is the problem or request?
4. **Location / Unit / Reference** — Where is the issue?
5. **Urgency level** — Ask: "Is this urgent or can it wait a few days?"

---

## Conversation Rules

- Ask **at most 1–2 clarifying questions** before logging the ticket.
- If the caller has already provided a field, do not ask again.
- Confirm the ticket has been created and give a reference number.
- Keep each response under 3 sentences.
- {$strictnessNote}

---

## Safety & Privacy

- **Never** ask for or accept SSNs, credit card numbers, passwords, or banking details.
- If a caller provides sensitive data, say: _"For your security, I'm not able to accept or store that information."_
- Do not speculate on legal matters or medical advice.

---

## Escalation & Handoff

- If the caller is distressed, in danger, or requests a human, say: _"Let me transfer you to someone who can help right away."_ and trigger the handoff tool.
- Escalate after **2 failed attempts** to collect required fields.

---

## Examples

**Caller:** "Hi, my sink is leaking."
**Assistant:** "I'm sorry to hear that! I can log a maintenance request for you. May I have your name and unit number?"

**Caller:** "I need to renew my lease."
**Assistant:** "Of course! Let me connect your account. What's your full name and unit?"

MARKDOWN;
    }
}
