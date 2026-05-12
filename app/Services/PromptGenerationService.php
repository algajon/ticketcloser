<?php

namespace App\Services;

use App\Models\PromptVersion;
use App\Models\Workspace;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PromptGenerationService
{
    protected string $lastGenerationMode = 'template';

    public function generate(array $input, Workspace $workspace, int $userId): PromptVersion
    {
        $assistantType = $input['assistant_type'] ?? 'bright_guide';
        $tone = $input['tone'] ?? 'professional';
        $strictness = $input['strictness'] ?? 'medium';
        $language = $input['language'] ?? 'en-US';
        $tools = $input['tools_enabled'] ?? [];
        $summary = trim((string) ($input['description'] ?? ''));
        $assistantName = trim((string) ($input['assistant_name'] ?? ''));
        $firstMessage = trim((string) ($input['first_message'] ?? ''));
        $currentPrompt = trim((string) ($input['current_prompt'] ?? ''));

        Log::info('PromptGenerationService: generating', [
            'workspace_id' => $workspace->id,
            'assistant_type' => $assistantType,
            'user_id' => $userId,
        ]);

        $systemPrompt = $this->buildPrompt(
            assistantType: $assistantType,
            tone: $tone,
            strictness: $strictness,
            language: $language,
            tools: $tools,
            description: $summary,
            workspace: $workspace,
            assistantName: $assistantName,
            firstMessage: $firstMessage,
            currentPrompt: $currentPrompt,
        );

        return PromptVersion::create([
            'workspace_id' => $workspace->id,
            'created_by' => $userId,
            'assistant_id' => $input['assistant_id'] ?? null,
            'assistant_type' => $assistantType,
            'tone' => $tone,
            'strictness' => $strictness,
            'tools_enabled' => $tools,
            'input_summary' => trim(implode("\n", array_filter([
                $summary,
                'Workspace: ' . $workspace->name,
                'Workflow: ' . (method_exists($workspace, 'useCaseLabel') ? $workspace->useCaseLabel() : 'General customer support'),
                filled($workspace->use_case_details ?? null) ? 'Business details: ' . trim((string) $workspace->use_case_details) : null,
                'Ticket label: ' . ($workspace->case_label ?: 'Ticket'),
                'Language: ' . $language,
                $assistantName !== '' ? 'Assistant: ' . $assistantName : null,
                $firstMessage !== '' ? 'Opening line: ' . $firstMessage : null,
            ]))),
            'output_markdown' => $systemPrompt,
        ]);
    }

    public function aiAvailable(): bool
    {
        return filled(config('services.openai.api_key'));
    }

    public function lastGenerationMode(): string
    {
        return $this->lastGenerationMode;
    }

    protected function buildPrompt(
        string $assistantType,
        string $tone,
        string $strictness,
        string $language,
        array $tools,
        string $description,
        Workspace $workspace,
        string $assistantName,
        string $firstMessage,
        string $currentPrompt
    ): string {
        $apiKey = config('services.openai.api_key');
        $baseUrl = config('services.openai.base_url', 'https://api.openai.com/v1');
        $model = config('services.openai.model', 'gpt-4o-mini');
        $this->lastGenerationMode = 'template';

        if ($apiKey) {
            try {
                $result = $this->callLlm(
                    apiKey: $apiKey,
                    baseUrl: $baseUrl,
                    model: $model,
                    assistantType: $assistantType,
                    tone: $tone,
                    strictness: $strictness,
                    language: $language,
                    tools: $tools,
                    description: $description,
                    workspace: $workspace,
                    assistantName: $assistantName,
                    firstMessage: $firstMessage,
                    currentPrompt: $currentPrompt,
                );
                $this->lastGenerationMode = 'ai';

                return $result;
            } catch (\Throwable $e) {
                Log::warning('PromptGenerationService: LLM call failed, using template', ['error' => $e->getMessage()]);
            }
        }

        return $this->templateFallback(
            assistantType: $assistantType,
            tone: $tone,
            strictness: $strictness,
            language: $language,
            tools: $tools,
            description: $description,
            workspace: $workspace,
            assistantName: $assistantName,
            firstMessage: $firstMessage,
            currentPrompt: $currentPrompt,
        );
    }

    protected function callLlm(
        string $apiKey,
        string $baseUrl,
        string $model,
        string $assistantType,
        string $tone,
        string $strictness,
        string $language,
        array $tools,
        string $description,
        Workspace $workspace,
        string $assistantName,
        string $firstMessage,
        string $currentPrompt
    ): string {
        $toolList = implode(', ', $tools) ?: 'none';
        $workspaceSummary = $this->workspaceSummary($workspace);
        $assistantLabel = $assistantName !== '' ? $assistantName : $workspace->name . ' assistant';
        $workflowLabel = $this->workflowLabel($assistantType);
        $openingLine = trim($firstMessage) !== ''
            ? $firstMessage
            : 'No fixed opening line provided. Start with a short, warm greeting that matches the business context.';

        $meta = <<<PROMPT
You are an expert voice assistant prompt engineer. Generate a top-quality system prompt in Markdown for an AI voice assistant.

Workspace:
{$workspaceSummary}

Assistant name:
{$assistantLabel}

Conversation shape:
{$workflowLabel}

User context:
{$description}

Existing prompt direction to preserve when useful:
{$currentPrompt}

Preferred opening line:
{$openingLine}

Tone:
{$tone}

Strictness:
{$strictness}

Primary language:
{$language}

Tools enabled:
{$toolList}

Use current voice-agent best practices:
- Write for spoken conversation, not chat.
- Keep replies short, clear, and easy to hear.
- Ask one question at a time.
- Make tool rules explicit, especially before actions that create, book, transfer, or change records.
- Include behavior for silence, interruptions, and unclear audio.
- Keep the assistant in the requested language unless the caller clearly switches.
- Respect the user's business context literally. Do not fall back to generic tech support wording unless the context says that.
- If the context suggests appointments, purchases, maintenance, intake, billing, or another domain, let the prompt clearly sound like that domain.

The prompt MUST include these Markdown sections:
## Business Context
## Role & Goal
## Tone & Voice Style
## Language Rules
## Allowed Tools & When to Use
## Required Fields to Collect
## Conversation Rules
## Handling Silence, Interruptions, and Unclear Audio
## Safety & Privacy
## Escalation & Handoff
## Examples (short, 1-2)

Special sequencing requirement:
- If both ticket creation and meeting booking are enabled, the prompt must explicitly say the assistant creates the ticket first and books the meeting second.
- If a caller asks to book first, the prompt must tell the assistant to explain the sequence clearly, then continue without sounding blocked or confused.
- The assistant should sound energetic or premium when the selected conversation shape calls for it, but never rushed, clipped, or interruptive.
- If a preferred opening line is provided, use that exact line as the call opener unless the business context clearly requires a small wording adjustment.

Keep it concise but comprehensive. Write ONLY the prompt, no meta-commentary.
PROMPT;

        $response = Http::withToken($apiKey)
            ->baseUrl($baseUrl)
            ->timeout(30)
            ->post('/chat/completions', [
                'model' => $model,
                'messages' => [['role' => 'user', 'content' => $meta]],
                'max_tokens' => 1400,
            ])
            ->throw();

        $content = trim((string) $response->json('choices.0.message.content', ''));

        if ($content === '') {
            throw new \RuntimeException('Prompt API returned an empty prompt payload.');
        }

        return $content;
    }

    protected function templateFallback(
        string $assistantType,
        string $tone,
        string $strictness,
        string $language,
        array $tools,
        string $description,
        Workspace $workspace,
        string $assistantName,
        string $firstMessage,
        string $currentPrompt
    ): string {
        $toolList = implode(', ', $tools) ?: '_none_';
        $workflowLabel = $this->workflowLabel($assistantType);
        $workflowGuidance = $this->workflowGuidance($assistantType);
        $toolBehaviorGuidance = $this->toolBehaviorGuidance($tools);
        $fallbackExample = $this->meetingFallbackExample($tools);
        $strictnessNote = match ($strictness) {
            'high' => 'Stay tightly inside the approved workflow. If the caller drifts, redirect quickly and clearly.',
            'low' => 'Stay flexible when a small amount of context is missing, but keep the conversation efficient.',
            default => 'Stay anchored to the workflow while using reasonable judgement for minor deviations.',
        };
        $toneNote = match ($tone) {
            'friendly' => 'Warm, calm, and reassuring without sounding casual.',
            'strict' => 'Formal, direct, and operationally focused.',
            default => 'Professional, concise, and courteous.',
        };
        $assistantLabel = $assistantName !== '' ? $assistantName : $workspace->name . ' assistant';
        $businessContext = trim($description) !== ''
            ? $description
            : 'Handle inbound calls for this workspace, capture the right details, and move the caller to the best next step.';
        $openingLineNote = trim($firstMessage) !== ''
            ? $firstMessage
            : 'No fixed opening line was provided. Start with a short, warm greeting that matches the business context.';
        $currentPromptNote = trim($currentPrompt) !== ''
            ? Str::limit(preg_replace('/\s+/', ' ', $currentPrompt), 420, '...')
            : 'No existing prompt draft was provided.';

        return <<<MARKDOWN
# {$assistantLabel} System Prompt

## Business Context

- Workspace: {$workspace->name}
- Ticket label: {$workspace->case_label}
- Default timezone: {$workspace->default_timezone}
- Conversation shape: {$workflowLabel}
- Business context to respect exactly: {$businessContext}
- Preferred opening line: {$openingLineNote}
- Existing draft direction to preserve when possible: {$currentPromptNote}

## Role & Goal

You are the voice assistant for {$workspace->name}. Your job is to keep the call grounded in the business context above, collect the right details efficiently, and move the caller to the correct next step without sounding generic.

{$workflowGuidance}

## Tone & Voice Style

- Sound natural when spoken aloud.
- Sound alert, confident, and easy to follow.
- Keep responses brief and easy to follow.
- Open the call with the preferred opening line when one is provided.
- Ask one question at a time.
- Avoid filler, long explanations, repeated confirmations, or robotic transitions.
- Do not talk over the caller or treat a short pause like the end of the turn.
- Tone target: {$toneNote}

## Language Rules

- Conduct the conversation in **{$language}** by default.
- If the caller clearly switches language and the business can support it, follow that change carefully.
- Do not mix languages in the same response unless needed for clarity.

## Allowed Tools & When to Use

**Tools enabled:** {$toolList}

- Use only the tools listed above.
- Confirm important actions before using any tool that creates, books, transfers, or changes records.
- Include requesterName and requesterEmail when they are known.
- After a successful tool call, explain the result in one short sentence.
{$toolBehaviorGuidance}

## Required Fields to Collect

1. Caller identity or name when relevant
2. Best callback detail if it is not already available
3. A clear summary of what the caller wants
4. Any location, account, order, appointment, or reference details the workflow requires
5. Urgency, timing, or follow-up preference when it matters

## Conversation Rules

- Keep the business context above in mind for every question you ask.
- Ask at most 1-2 clarifying questions before moving toward action.
- If the caller already provided a detail, do not ask for it again.
- Read back a short summary before any tool with side effects.
- Keep each response under 3 short sentences when possible.
- When the caller wants both help and a follow-up, log the request first, then handle the booking.
- {$strictnessNote}

## Handling Silence, Interruptions, and Unclear Audio

- If audio is unclear, ask the caller to repeat only the missing detail.
- If the caller interrupts, stop cleanly, answer the interruption, and then return to the workflow.
- If the caller is silent, prompt once briefly and calmly.
- If a required detail cannot be collected after two attempts, offer the next safe fallback or handoff.

## Safety & Privacy

- Never ask for or store passwords, full payment card numbers, social security numbers, or unrelated sensitive data.
- If the caller shares sensitive data, tell them you cannot accept or store it.
- Do not invent policies, outcomes, ticket numbers, or appointments.

## Escalation & Handoff

- Escalate if the caller is distressed, reports an emergency, or explicitly asks for a human.
- Escalate if the assistant lacks enough information to complete the workflow safely.
- Keep escalation language short, calm, and specific.

## Examples (short, 1-2)

**Caller:** "I need help with this request."
**Assistant:** "I can help with that. Can you tell me the main issue so I can log it correctly?"

{$fallbackExample}

MARKDOWN;
    }

    private function workflowLabel(string $assistantType): string
    {
        return match ($assistantType) {
            'bright_guide' => 'Bright guide',
            'steady_operator' => 'Steady operator',
            'confident_closer' => 'Confident closer',
            'premium_concierge' => 'Premium concierge',
            'custom' => 'Custom',
            default => 'Bright guide',
        };
    }

    private function workflowGuidance(string $assistantType): string
    {
        return match ($assistantType) {
            'bright_guide' => 'Lead with energy and clarity, but keep the pacing patient enough that callers never feel cut off.',
            'steady_operator' => 'Keep the caller on track with calm, orderly questions and extra room for pauses or mid-thought thinking.',
            'confident_closer' => 'Move with strong next-step energy while still letting the caller finish before you act.',
            'premium_concierge' => 'Sound polished and premium, with clean transitions into confirmation and follow-up scheduling.',
            'custom' => 'Respect the user-provided context first and keep the workflow flexible without becoming vague.',
            default => 'Lead with energy and clarity while keeping the conversation smooth and interruption-free.',
        };
    }

    private function toolBehaviorGuidance(array $tools): string
    {
        $normalized = collect($tools)
            ->map(fn ($tool) => (string) $tool)
            ->values()
            ->all();

        if (in_array('create_ticket', $normalized, true) && in_array('book_meeting', $normalized, true)) {
            return "- Create the ticket before booking any meeting.\n- If the caller asks to book first, explain that you will log the request first and then book the follow-up right after the ticket is created.\n- Do not sound blocked, confused, or apologetic about this sequence.";
        }

        return '- Use tools in the cleanest order for the caller, without making them repeat details.';
    }

    private function meetingFallbackExample(array $tools): string
    {
        $normalized = collect($tools)
            ->map(fn ($tool) => (string) $tool)
            ->values()
            ->all();

        if (in_array('create_ticket', $normalized, true) && in_array('book_meeting', $normalized, true)) {
            return <<<MARKDOWN
**Caller:** "Book me for tomorrow at 3 PM."
**Assistant:** "I can do that. I will log the request first, then I will book the follow-up right after that. Before I do, what is the main issue you are calling about?"
MARKDOWN;
        }

        return <<<MARKDOWN
**Caller:** "I already gave you my number. I just want the follow-up booked."
**Assistant:** "Understood. I will use the number already on file. What time would you like the follow-up scheduled?"
MARKDOWN;
    }

    private function workspaceSummary(Workspace $workspace): string
    {
        $useCaseLabel = method_exists($workspace, 'useCaseLabel') ? $workspace->useCaseLabel() : 'General customer support';
        $useCaseDetails = trim((string) ($workspace->use_case_details ?? ''));

        return implode("\n", [
            '- Workspace name: ' . $workspace->name,
            '- Ticket label: ' . ($workspace->case_label ?: 'Ticket'),
            '- Default timezone: ' . ($workspace->default_timezone ?: 'UTC'),
            '- Primary workflow: ' . $useCaseLabel,
            $useCaseDetails !== '' ? '- Business-specific details: ' . $useCaseDetails : null,
            '- Plan key: ' . ($workspace->plan_key ?: 'free'),
        ]);
    }
}
