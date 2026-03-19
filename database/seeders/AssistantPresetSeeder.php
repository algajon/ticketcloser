<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AssistantPresetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $presets = [
            [
                'key' => 'customer_support',
                'name' => 'Customer Support',
                'notes' => 'Snappy but not interrupting.',
                'vapi_payload_json' => [
                    'startSpeakingPlan' => [
                        'waitSeconds' => 0.5,
                        'smartEndpointingPlan' => ['provider' => 'livekit'],
                    ],
                    'stopSpeakingPlan' => [
                        'numWords' => 2,
                        'voiceSeconds' => 0.2,
                        'backoffSeconds' => 0.9,
                        'acknowledgementPhrases' => ["yeah", "okay", "right", "mm-hmm", "got it", "i see"],
                        'interruptionPhrases' => ["stop", "hold on", "wait", "one second", "pause"],
                    ],
                    'firstMessage' => 'Hi! Thanks for calling support. How can I help you today?',
                    'systemPrompt' => "You are a friendly customer support agent for {{company_name}}.\n\nGoals:\n1) Understand the customer's issue.\n2) If the caller's phone number linked to the account is not clear, ask for it (E.164 format, +1...).\n3) Determine category and priority.\n4) Read back a short summary and ask for confirmation.\n5) ONLY after confirmation, call createCase.\n6) Inform the caller of their case number.",
                ],
            ],
            [
                'key' => 'maintenance_intake',
                'name' => 'Maintenance Intake',
                'notes' => 'More patient timing for maintenance issues.',
                'vapi_payload_json' => [
                    'startSpeakingPlan' => [
                        'waitSeconds' => 0.7,
                    ],
                    'stopSpeakingPlan' => [
                        'numWords' => 2,
                        'voiceSeconds' => 0.25,
                        'backoffSeconds' => 1.0,
                        'acknowledgementPhrases' => ["yeah", "okay", "right", "mm-hmm", "got it", "i see"],
                        'interruptionPhrases' => ["stop", "hold on", "wait", "one second", "pause"],
                    ],
                    'firstMessage' => 'Hello. I can help you submit a new maintenance request. What seems to be the issue?',
                    'systemPrompt' => "You are a maintenance intake coordinator for {{company_name}}.\n\nGoals:\n1) Gather full details of the maintenance issue (location, severity, when it started).\n2) Ask for the requester's phone number if not provided.\n3) Summarize the issue and ask for confirmation.\n4) Call createCase.\n5) Provide the case number to the caller.",
                ],
            ],
            [
                'key' => 'mortgage_intake',
                'name' => 'Mortgage Intake',
                'notes' => 'Slowest / most patient timing.',
                'vapi_payload_json' => [
                    'startSpeakingPlan' => [
                        'waitSeconds' => 0.8,
                    ],
                    'stopSpeakingPlan' => [
                        'numWords' => 3,
                        'voiceSeconds' => 0.25,
                        'backoffSeconds' => 1.1,
                        'acknowledgementPhrases' => ["yeah", "okay", "right", "mm-hmm", "got it", "i see"],
                        'interruptionPhrases' => ["stop", "hold on", "wait", "one second", "pause"],
                    ],
                    'firstMessage' => 'Hello, thank you for calling about a mortgage. How can I help you get started?',
                    'systemPrompt' => "You are a mortgage intake specialist for {{company_name}}.\n\nGoals:\n1) Understand what type of mortgage the caller is interested in.\n2) Collect basic intake information carefully and patiently.\n3) Ask for the requester's phone number if not known.\n4) Summarize and ask for confirmation.\n5) Call createCase to open their file.\n6) Give them the case number for reference.",
                ],
            ],
        ];

        foreach ($presets as $preset) {
            \App\Models\AssistantPreset::updateOrCreate(
                ['key' => $preset['key']],
                [
                    'name' => $preset['name'],
                    'notes' => $preset['notes'],
                    'vapi_payload_json' => $preset['vapi_payload_json'],
                ]
            );
        }
    }
}
