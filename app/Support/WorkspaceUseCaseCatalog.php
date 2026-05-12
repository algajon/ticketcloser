<?php

namespace App\Support;

use App\Models\AssistantPreset;
use App\Models\Workspace;

class WorkspaceUseCaseCatalog
{
    public const OTHER = 'other';

    public static function options(): array
    {
        return array_values(self::definitions());
    }

    public static function keys(): array
    {
        return array_keys(self::definitions());
    }

    public static function teamSizeOptions(): array
    {
        return [
            ['value' => 'just_me', 'label' => 'Just me'],
            ['value' => '2_5', 'label' => '2-5 people'],
            ['value' => '6_15', 'label' => '6-15 people'],
            ['value' => '16_50', 'label' => '16-50 people'],
            ['value' => '50_plus', 'label' => '50+ people'],
        ];
    }

    public static function presetChoices(): array
    {
        return [
            [
                'key' => 'steady_operator',
                'label' => 'Professional',
                'description' => 'Calm, clear, and structured.',
            ],
            [
                'key' => 'bright_guide',
                'label' => 'Warm',
                'description' => 'Friendly, upbeat, and easygoing.',
            ],
            [
                'key' => 'confident_closer',
                'label' => 'Fast-paced',
                'description' => 'Direct, sharp, and quick to act.',
            ],
            [
                'key' => 'premium_concierge',
                'label' => 'Premium',
                'description' => 'Refined, polished, and high-touch.',
            ],
        ];
    }

    public static function languageOptions(): array
    {
        return RegionalPilotStackCatalog::languageOptions();
    }

    public static function definition(?string $key, ?string $details = null): array
    {
        $definitions = self::definitions();
        $key = array_key_exists((string) $key, $definitions) ? (string) $key : 'customer_support';
        $definition = $definitions[$key];

        if ($key === self::OTHER && filled($details)) {
            $definition['description'] = 'Built around: ' . trim((string) $details);
        }

        return $definition;
    }

    public static function assistantDraft(Workspace $workspace): array
    {
        $definition = self::definition($workspace->use_case, $workspace->use_case_details);
        $captureFields = self::currentCaptureFieldLabels($workspace, $definition);
        $businessContext = $workspace->use_case === self::OTHER && filled($workspace->use_case_details)
            ? trim((string) $workspace->use_case_details)
            : $definition['business_context'];
        $assistantName = trim((string) ($workspace->default_assistant_name ?: $definition['assistant_name']));
        $presetKey = AssistantPreset::normalizeKey($workspace->default_preset_key ?: $definition['preset_key']);
        $caseLabel = trim((string) ($workspace->case_label ?: $definition['case_label']));
        $languageCode = trim((string) ($workspace->preferredLanguageCode() ?: ($definition['language_code'] ?? RegionalPilotStackCatalog::defaultLanguageForMarket($workspace->primaryMarket()))));
        $recordLabel = strtolower((string) ($caseLabel ?: 'Ticket'));
        $prompt = $workspace->use_case === 'property_management'
            ? self::propertyManagementPrompt($workspace, $definition, $recordLabel, $captureFields)
            : self::genericPrompt($workspace, $definition, $businessContext, $recordLabel, $captureFields);
        $firstMessage = self::preferredFirstMessage($workspace, $definition, $languageCode);
        $regionalPlaybook = RegionalPilotStackCatalog::pilotPlaybook($workspace->primaryMarket(), $definition['key'], $languageCode);

        return [
            'use_case_key' => $definition['key'],
            'use_case_label' => $definition['label'],
            'short_description' => $definition['short_description'],
            'description' => $definition['description'],
            'assistant_name' => $assistantName,
            'preset_key' => $presetKey,
            'case_label' => $caseLabel,
            'language_code' => $languageCode,
            'required_fields' => $captureFields,
            'capture_fields' => $definition['capture_fields'],
            'category_options' => $definition['category_options'],
            'priority_rules' => $definition['priority_rules'],
            'call_flow' => $definition['call_flow'],
            'common_calls' => $definition['common_calls'],
            'ops_outcomes' => $definition['ops_outcomes'],
            'emergency_examples' => $definition['emergency_examples'],
            'workflow_summary' => $definition['workflow_summary'],
            'regional_playbook' => $regionalPlaybook,
            'first_message' => $firstMessage,
            'prompt' => $prompt,
        ];
    }

    public static function applyWorkspaceDefaults(Workspace $workspace): array
    {
        $draft = self::assistantDraft($workspace);

        return [
            'case_label' => $draft['case_label'],
            'system_prompt' => $draft['prompt'],
            'required_fields' => $draft['required_fields'],
            'category_options' => $draft['category_options'],
            'priority_rules' => $draft['priority_rules'],
            'assistant_name' => $draft['assistant_name'],
            'preset_key' => $draft['preset_key'],
            'call_flow' => $draft['call_flow'],
        ];
    }

    public static function defaultCaptureFieldKeys(?string $useCase, ?string $details = null): array
    {
        return collect(self::definition($useCase, $details)['capture_fields'] ?? [])
            ->filter(fn (array $field) => (bool) ($field['default'] ?? false))
            ->pluck('key')
            ->values()
            ->all();
    }

    public static function captureFieldKeysForLabels(?string $useCase, array $labels, ?string $details = null): array
    {
        $labelLookup = collect(self::definition($useCase, $details)['capture_fields'] ?? [])
            ->mapWithKeys(fn (array $field) => [strtolower((string) $field['label']) => (string) $field['key']]);

        return collect($labels)
            ->map(fn ($label) => $labelLookup[strtolower((string) $label)] ?? null)
            ->filter()
            ->values()
            ->all();
    }

    public static function resolveCaptureFields(?string $useCase, array $selectedKeys, ?string $details = null): array
    {
        $fieldLookup = collect(self::definition($useCase, $details)['capture_fields'] ?? [])
            ->mapWithKeys(fn (array $field) => [(string) $field['key'] => (string) $field['label']]);

        $resolved = collect($selectedKeys)
            ->map(fn ($key) => $fieldLookup[(string) $key] ?? null)
            ->filter()
            ->values()
            ->all();

        return $resolved !== [] ? $resolved : self::definition($useCase, $details)['required_fields'];
    }

    protected static function currentCaptureFieldLabels(Workspace $workspace, array $definition): array
    {
        $workspace->loadMissing('intakeConfig');

        $requiredFields = $workspace->intakeConfig?->required_fields;

        if (is_array($requiredFields) && $requiredFields !== []) {
            return $requiredFields;
        }

        return $definition['required_fields'];
    }

    protected static function definitions(): array
    {
        $definitions = [
            'property_management' => [
                'key' => 'property_management',
                'label' => 'Property management / maintenance',
                'short_description' => 'Maintenance requests, resident issues, and urgent property calls.',
                'description' => 'Capture maintenance calls, triage urgent property issues, and give your team clean tickets with unit and access details.',
                'assistant_name' => 'Maintenance desk',
                'preset_key' => 'steady_operator',
                'case_label' => 'Request',
                'language_code' => 'en-US',
                'business_context' => 'You handle tenant maintenance requests, resident details, property and unit context, access notes, scheduling preferences, and urgent building issues.',
                'workflow_summary' => 'Turn maintenance calls into ticket-ready updates with property, unit, urgency, and access details already organized.',
                'required_fields' => ['Full name', 'Callback number', 'Property or building', 'Unit number', 'Issue summary', 'When it started', 'Urgency or safety risk', 'Access details', 'Best time for follow-up'],
                'capture_fields' => [
                    ['key' => 'full_name', 'label' => 'Caller name', 'default' => true],
                    ['key' => 'callback_number', 'label' => 'Callback number', 'default' => true],
                    ['key' => 'property_name', 'label' => 'Property or building', 'default' => true],
                    ['key' => 'unit_number', 'label' => 'Unit number', 'default' => true],
                    ['key' => 'issue_summary', 'label' => 'Issue summary', 'default' => true],
                    ['key' => 'category', 'label' => 'Category', 'default' => false],
                    ['key' => 'priority', 'label' => 'Urgency or safety risk', 'default' => true],
                    ['key' => 'access_details', 'label' => 'Access details', 'default' => true],
                    ['key' => 'visit_window', 'label' => 'Best time for follow-up', 'default' => false],
                    ['key' => 'email', 'label' => 'Email if useful', 'default' => false],
                ],
                'category_options' => ['maintenance', 'plumbing', 'hvac', 'electrical', 'appliance', 'lockout', 'pest', 'water leak', 'after_hours'],
                'priority_rules' => ['water leak' => 'critical', 'no heat' => 'critical', 'gas smell' => 'critical', 'electrical hazard' => 'critical', 'lockout' => 'high', 'routine issue' => 'normal'],
                'call_flow' => [
                    'Identify the resident and confirm the property address or unit first.',
                    'Understand the issue, when it started, and whether it is active or unsafe.',
                    'Collect access details, entry notes, and the best visit window.',
                    'Read back a tight maintenance summary before creating the request.',
                    'Create the request, then handle follow-up scheduling if needed.',
                ],
                'common_calls' => [
                    'Leaking toilet or sink',
                    'No heat or no air conditioning',
                    'Lockout or building access issue',
                    'Appliance or cosmetic maintenance request',
                ],
                'ops_outcomes' => [
                    'Ticket includes the resident, property, and unit context.',
                    'Urgent issues are flagged before the team follows up.',
                    'Access notes and visit windows are captured on the first call.',
                ],
                'emergency_examples' => [
                    'Active water leak',
                    'No heat in dangerous weather',
                    'Gas smell or electrical hazard',
                    'Lockout after hours',
                ],
            ],
            'front_desk' => [
                'key' => 'front_desk',
                'label' => 'Front desk / receptionist',
                'short_description' => 'Inbound calls, message taking, and general routing.',
                'description' => 'Answer inbound calls, take messages, route requests, and keep follow-up moving without sounding robotic.',
                'assistant_name' => 'Front desk',
                'preset_key' => 'bright_guide',
                'case_label' => 'Ticket',
                'language_code' => 'en-US',
                'business_context' => 'You act like a polished front desk for a busy business, helping callers get to the right next step quickly.',
                'workflow_summary' => 'Turn front-desk calls into clean tickets, messages, and booked follow-up without losing context.',
                'required_fields' => ['Full name', 'Callback number', 'Company or context', 'Reason for the call', 'Urgency', 'Best next step'],
                'capture_fields' => [
                    ['key' => 'full_name', 'label' => 'Caller name', 'default' => true],
                    ['key' => 'phone_number', 'label' => 'Phone number', 'default' => true],
                    ['key' => 'issue_summary', 'label' => 'Reason for the call', 'default' => true],
                    ['key' => 'category', 'label' => 'Category', 'default' => true],
                    ['key' => 'callback_number', 'label' => 'Callback number', 'default' => false],
                    ['key' => 'company', 'label' => 'Company or context', 'default' => false],
                    ['key' => 'appointment_date', 'label' => 'Appointment date', 'default' => false],
                    ['key' => 'priority', 'label' => 'Urgency', 'default' => false],
                    ['key' => 'best_next_step', 'label' => 'Best next step', 'default' => true],
                ],
                'category_options' => ['message', 'appointment', 'sales', 'support', 'general'],
                'priority_rules' => ['urgent callback' => 'high', 'general message' => 'normal'],
                'call_flow' => [
                    'Greet the caller warmly and ask how you can help.',
                    'Capture the caller name and the reason for the call.',
                    'Clarify whether they need a message, a booking, or the right team.',
                    'Summarize the request clearly before creating the ticket.',
                    'Book follow-up only after the request has been logged.',
                ],
            ],
            'it_support' => [
                'key' => 'it_support',
                'label' => 'IT support',
                'short_description' => 'Support issues, outages, and troubleshooting calls.',
                'description' => 'Capture support issues, triage outages, and keep troubleshooting or follow-up requests organized.',
                'assistant_name' => 'IT support line',
                'preset_key' => 'confident_closer',
                'case_label' => 'Ticket',
                'language_code' => 'en-US',
                'business_context' => 'You handle inbound IT support calls, capture the issue clearly, and move the caller toward the right support next step.',
                'workflow_summary' => 'Turn support calls into structured tickets with the impacted system, urgency, and next-step ownership already captured.',
                'required_fields' => ['Full name', 'Callback number', 'Company or team', 'Issue summary', 'System affected', 'Urgency', 'Best time for follow-up'],
                'capture_fields' => [
                    ['key' => 'full_name', 'label' => 'Caller name', 'default' => true],
                    ['key' => 'phone_number', 'label' => 'Phone number', 'default' => true],
                    ['key' => 'issue_summary', 'label' => 'Issue summary', 'default' => true],
                    ['key' => 'system_affected', 'label' => 'System affected', 'default' => true],
                    ['key' => 'priority', 'label' => 'Urgency', 'default' => true],
                    ['key' => 'callback_number', 'label' => 'Callback number', 'default' => false],
                    ['key' => 'email', 'label' => 'Email if useful', 'default' => false],
                    ['key' => 'company', 'label' => 'Company or team', 'default' => false],
                    ['key' => 'category', 'label' => 'Category', 'default' => false],
                ],
                'category_options' => ['access', 'hardware', 'software', 'network', 'outage', 'general'],
                'priority_rules' => ['outage' => 'critical', 'cannot log in' => 'high', 'how-to question' => 'normal'],
                'call_flow' => [
                    'Find out who is calling and what system or tool is affected.',
                    'Clarify whether the issue is blocking work right now.',
                    'Capture the cleanest possible issue summary.',
                    'Read the issue back before creating the ticket.',
                    'Create the ticket, then handle callback scheduling if needed.',
                ],
            ],
            'customer_support' => [
                'key' => 'customer_support',
                'label' => 'General customer support',
                'short_description' => 'Customer questions, billing issues, and routine support calls.',
                'description' => 'Turn customer calls into clean support records with clear next steps and fast follow-up.',
                'assistant_name' => 'Support line',
                'preset_key' => 'premium_concierge',
                'case_label' => 'Ticket',
                'language_code' => 'en-US',
                'business_context' => 'You handle general customer support calls and turn them into clean, confirmed support records with strong follow-up.',
                'workflow_summary' => 'Turn customer calls into clean tickets with issue context, urgency, and the right follow-up path.',
                'required_fields' => ['Full name', 'Callback number', 'Email if useful', 'Issue summary', 'Category', 'Urgency'],
                'capture_fields' => [
                    ['key' => 'full_name', 'label' => 'Caller name', 'default' => true],
                    ['key' => 'phone_number', 'label' => 'Phone number', 'default' => true],
                    ['key' => 'issue_summary', 'label' => 'Issue summary', 'default' => true],
                    ['key' => 'category', 'label' => 'Category', 'default' => true],
                    ['key' => 'callback_number', 'label' => 'Callback number', 'default' => false],
                    ['key' => 'email', 'label' => 'Email if useful', 'default' => false],
                    ['key' => 'priority', 'label' => 'Urgency', 'default' => false],
                    ['key' => 'order_number', 'label' => 'Order number', 'default' => false],
                ],
                'category_options' => ['account', 'billing', 'technical', 'order', 'general'],
                'priority_rules' => ['service down' => 'critical', 'billing issue' => 'high', 'general question' => 'normal'],
                'call_flow' => [
                    'Welcome the caller and understand what they need.',
                    'Collect the shortest set of details needed to help them.',
                    'Confirm the issue and urgency in one clean summary.',
                    'Create the ticket after confirmation.',
                    'Offer a booked follow-up when it would help.',
                ],
            ],
            self::OTHER => [
                'key' => self::OTHER,
                'label' => 'Other',
                'short_description' => 'Bring your own workflow and we will prefill around it.',
                'description' => 'Use your own business context while still getting a guided starting point.',
                'assistant_name' => 'Business line',
                'preset_key' => 'custom',
                'case_label' => 'Request',
                'language_code' => 'en-US',
                'business_context' => 'Handle the caller\'s request based on the business context the workspace owner provides.',
                'workflow_summary' => 'Start with a flexible intake flow, then adapt the assistant to match your operation.',
                'required_fields' => ['Full name', 'Callback number', 'Reason for the call', 'Key details', 'Best next step'],
                'capture_fields' => [
                    ['key' => 'full_name', 'label' => 'Caller name', 'default' => true],
                    ['key' => 'phone_number', 'label' => 'Phone number', 'default' => true],
                    ['key' => 'issue_summary', 'label' => 'Issue summary', 'default' => true],
                    ['key' => 'category', 'label' => 'Category', 'default' => false],
                    ['key' => 'callback_number', 'label' => 'Callback number', 'default' => false],
                    ['key' => 'email', 'label' => 'Email if useful', 'default' => false],
                    ['key' => 'priority', 'label' => 'Priority', 'default' => false],
                    ['key' => 'best_next_step', 'label' => 'Best next step', 'default' => true],
                ],
                'category_options' => ['general'],
                'priority_rules' => ['urgent' => 'high'],
                'call_flow' => [
                    'Understand the caller\'s request in plain language.',
                    'Collect only the details needed for the business to follow up.',
                    'Summarize the request clearly before taking action.',
                    'Create the ticket first and book follow-up second.',
                ],
            ],
        ];

        return collect($definitions)->map(function (array $definition) {
            return array_merge([
                'workflow_summary' => 'Turn calls into clear, confirmed tickets and follow-up.',
                'common_calls' => [],
                'ops_outcomes' => [],
                'emergency_examples' => [],
                'short_description' => $definition['description'] ?? '',
                'language_code' => 'en-US',
                'capture_fields' => [],
            ], $definition);
        })->all();
    }

    protected static function genericPrompt(Workspace $workspace, array $definition, string $businessContext, string $recordLabel, array $captureFields): string
    {
        $languageCode = strtolower($workspace->preferredLanguageCode());
        $primaryMarket = $workspace->primaryMarket();
        $languageBehavior = self::languageBehaviorRules($workspace, $languageCode);

        return implode("\n", [
            "You are the phone assistant for {$workspace->name}.",
            '',
            'What you handle:',
            $businessContext,
            '',
            'Conversation rules:',
            '- Sound natural, clear, and easy to follow.',
            '- Ask one question at a time.',
            '- Keep replies short and spoken, not robotic.',
            '- Ask for the caller\'s full name and politely confirm the spelling once when it matters for follow-up.',
            '- If the caller already gave you a detail, do not ask for it again.',
            '- Summarize the issue clearly before taking action.',
            "- Create the {$recordLabel} before booking any meeting or follow-up.",
            '- If the caller asks to book first, explain that you will log the request first and then book the follow-up right after that.',
            '- If audio is unclear, ask only for the missing detail again.',
            collect($languageBehavior)->map(fn (string $rule) => '- ' . $rule)->implode("\n"),
            '',
            'Information to collect:',
            collect($captureFields)->map(fn (string $field) => '- ' . $field)->implode("\n"),
            '',
            'Preferred call flow:',
            collect($definition['call_flow'])->map(fn (string $step) => '- ' . $step)->implode("\n"),
            '',
            'Follow-up behavior:',
            "- If a meeting is needed and calendar tools are available, confirm the requested time only after the {$recordLabel} exists.",
            '- If booking fails, let the caller know the request was saved for follow-up.',
            $primaryMarket === RegionalPilotStackCatalog::UAE
                ? '- If the workspace is serving the UAE, treat WhatsApp callbacks and building/community names as useful context when the caller gives them.'
                : '',
        ]);
    }

    protected static function propertyManagementPrompt(Workspace $workspace, array $definition, string $recordLabel, array $captureFields): string
    {
        $languageCode = strtolower($workspace->preferredLanguageCode());
        $primaryMarket = $workspace->primaryMarket();
        $languageBehavior = self::languageBehaviorRules($workspace, $languageCode);

        return implode("\n", [
            "You are the property maintenance intake assistant for {$workspace->name}.",
            '',
            'Your job is to answer maintenance calls clearly, collect the right details, create a clean maintenance request, and help the property team move quickly.',
            '',
            'What you handle:',
            '- Tenant maintenance requests and resident follow-up.',
            '- Property, building, and unit-specific issues.',
            '- Urgent conditions like active leaks, no heat, and building access problems.',
            '- Access notes, preferred visit windows, and next-step scheduling after the request exists.',
            '',
            'How to sound:',
            '- Calm, professional, and easy to follow.',
            '- Warm enough to feel helpful, but never casual or chatty.',
            '- Ask one question at a time and keep the call moving.',
            '- Do not ask for the same detail twice if it is already clear.',
            '- Summarize the issue back before taking action.',
            collect($languageBehavior)->map(fn (string $rule) => '- ' . $rule)->implode("\n"),
            '',
            'Always collect:',
            collect($captureFields)->map(fn (string $field) => '- ' . $field)->implode("\n"),
            '',
            'Urgency rules:',
            '- Treat water leaks, no heat, gas smell, electrical hazards, and lockouts as urgent issues.',
            '- If the issue sounds unsafe, say you are marking it as urgent and keep collecting only the details needed to move fast.',
            '- Never promise a technician or time slot unless it is actually confirmed.',
            '',
            'Preferred call flow:',
            collect($definition['call_flow'])->map(fn (string $step) => '- ' . $step)->implode("\n"),
            '',
            $primaryMarket === RegionalPilotStackCatalog::UAE
                ? 'UAE property-management notes:'
                : '',
            $primaryMarket === RegionalPilotStackCatalog::UAE
                ? '- Building, tower, or community name is important context when the caller gives it.'
                : '',
            $primaryMarket === RegionalPilotStackCatalog::UAE
                ? '- If the resident gives a WhatsApp callback preference, capture it as part of the contact details.'
                : '',
            $primaryMarket === RegionalPilotStackCatalog::UAE
                ? '- Capture gate, parking, security desk, or contractor access instructions if they could block a visit.'
                : '',
            $primaryMarket === RegionalPilotStackCatalog::UAE
                ? '- Keep the tone polished and premium for high-trust property-management teams.'
                : '',
            $primaryMarket === RegionalPilotStackCatalog::UAE
                ? ''
                : '',
            'Follow-up behavior:',
            "- Create the {$recordLabel} before trying to book any visit or callback.",
            '- When you create the request, include the property or building, unit, access notes, and preferred visit window when you know them.',
            '- If the caller asks for a time first, explain that you will log the issue first and then handle scheduling right after that.',
            '- If booking fails, let the caller know the request was saved and the property team will follow up.',
        ]);
    }

    protected static function languageBehaviorRules(Workspace $workspace, string $languageCode): array
    {
        $primaryMarket = $workspace->primaryMarket();

        if ($primaryMarket === RegionalPilotStackCatalog::UAE && str_starts_with($languageCode, 'ar-')) {
            return [
                'Default to Arabic, but switch smoothly to English if the caller prefers English.',
                'If the caller mixes Arabic and English naturally, follow the last language they used instead of translating the whole conversation.',
                'Keep addresses, unit numbers, building names, and access instructions in the clearest format the caller uses.',
            ];
        }

        if ($primaryMarket === RegionalPilotStackCatalog::UAE) {
            return [
                'Start in English, but switch smoothly to Arabic if the caller prefers Arabic.',
                'If the caller mixes Arabic and English naturally, follow the last language they used instead of forcing one language.',
                'Keep addresses, unit numbers, building names, and access instructions in the clearest format the caller uses.',
            ];
        }

        if (str_starts_with($languageCode, 'ar-')) {
            return [
                'Default to Arabic unless the caller clearly prefers English.',
            ];
        }

        return [
            'Keep the call in the language the caller is using unless they clearly switch.',
        ];
    }

    protected static function preferredFirstMessage(Workspace $workspace, array $definition, string $languageCode): string
    {
        $languageCode = strtolower($languageCode);
        $primaryMarket = $workspace->primaryMarket();

        if ($definition['key'] === 'property_management' && str_starts_with($languageCode, 'ar-')) {
            return 'مرحبا، مع فريق الصيانة. كيف أقدر أساعدك اليوم؟';
        }

        if ($definition['key'] === 'property_management' && $primaryMarket === RegionalPilotStackCatalog::UAE) {
            return 'Hello, you have reached the property maintenance team. How can I help you today?';
        }

        return self::defaultFirstMessage($workspace, $definition, $languageCode);
    }

    protected static function defaultFirstMessage(Workspace $workspace, array $definition, string $languageCode): string
    {
        $languageCode = strtolower($languageCode);
        $primaryMarket = $workspace->primaryMarket();

        if ($definition['key'] === 'property_management' && str_starts_with($languageCode, 'ar-')) {
            return 'مرحبا، شكرا لاتصالك بفريق الصيانة. كيف أقدر أساعدك اليوم؟';
        }

        if ($definition['key'] === 'property_management' && $primaryMarket === RegionalPilotStackCatalog::UAE) {
            return 'Hi, thanks for calling the property maintenance team. How can I help you today?';
        }

        return match ($definition['key']) {
            'property_management' => 'Hi, thanks for calling maintenance. What issue can I help you report today?',
            'front_desk' => 'Hi, thanks for calling. How can I help you today?',
            'it_support' => 'Hi, thanks for calling IT support. What can I help you with today?',
            default => 'Hi, thanks for calling. How can I help you today?',
        };
    }
}
