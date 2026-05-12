<?php

/**
 * Plan definitions for tickIt.
 *
 * Limits of -1 = unlimited.
 */
return [
    'free' => [
        'label' => 'Free Trial',
        'price_monthly' => 0,
        'stripe_price_env' => null,
        'currency' => 'EUR',
        'max_workspaces' => 1,
        'max_minutes' => 5,
        'overage_per_minute' => null,
        'max_assistants' => 1,
        'max_phone_numbers' => 1,
        'max_cases' => 25,
        'features' => ['basic_intake', 'transcript', 'calendar_booking', 'prompt_writer'],
        'feature_highlights' => [
            '5 trial minutes to test live calls',
            '1 assistant and 1 phone number',
            'Case creation, transcripts, and contacts',
            'Prompt writer and basic scheduling tools',
        ],
        'badge_color' => 'slate',
        'description' => 'Build a real demo, take a few live calls, and see how tickIt fits your workflow.',
        'recommended_for' => 'Testing one workflow before rollout',
        'usage_copy' => 'No overage on free. Upgrade when you need more minutes.',
    ],

    'startup' => [
        'label' => 'Launch',
        'price_monthly' => 149,
        'stripe_price_env' => 'STRIPE_PRICE_STARTUP',
        'currency' => 'EUR',
        'max_workspaces' => -1,
        'max_minutes' => 250,
        'overage_per_minute' => 0.32,
        'max_assistants' => 2,
        'max_phone_numbers' => 1,
        'max_cases' => -1,
        'features' => ['basic_intake', 'transcript', 'calendar_booking', 'prompt_writer', 'contacts'],
        'feature_highlights' => [
            '250 included voice minutes each month',
            '1 live number and 2 assistants',
            'Case creation, transcripts, and contact capture',
            'Prompt writer plus calendar handoff',
        ],
        'badge_color' => 'blue',
        'description' => 'For one team that wants missed calls to turn into clean tickets and follow-up.',
        'recommended_for' => 'Single-site teams and lean support desks',
        'usage_copy' => 'Base fee plus €0.32 for each extra minute above the included 250.',
    ],

    'pro' => [
        'label' => 'Momentum',
        'price_monthly' => 449,
        'stripe_price_env' => 'STRIPE_PRICE_PRO',
        'currency' => 'EUR',
        'max_workspaces' => -1,
        'max_minutes' => 1200,
        'overage_per_minute' => 0.24,
        'max_assistants' => 6,
        'max_phone_numbers' => 3,
        'max_cases' => -1,
        'features' => ['basic_intake', 'transcript', 'calendar_booking', 'prompt_writer', 'priority_support', 'advanced_analytics', 'contacts', 'usage_analytics'],
        'feature_highlights' => [
            '1,200 included voice minutes each month',
            '3 live numbers and 6 assistants',
            'Call analytics, approvals, and stronger reporting',
            'Built for busy front desks, maintenance, and support teams',
        ],
        'badge_color' => 'indigo',
        'description' => 'For teams with steady call volume that want cleaner ops without jumping straight to enterprise.',
        'recommended_for' => 'Property management, reception, and IT support teams',
        'usage_copy' => 'Base fee plus €0.24 for each extra minute above the included 1,200.',
    ],

    'enterprise' => [
        'label' => 'Enterprise',
        'price_monthly' => 1249,
        'stripe_price_env' => 'STRIPE_PRICE_ENTERPRISE',
        'currency' => 'EUR',
        'max_workspaces' => -1,
        'max_minutes' => 4000,
        'overage_per_minute' => 0.16,
        'max_assistants' => 20,
        'max_phone_numbers' => 10,
        'max_cases' => -1,
        'features' => ['basic_intake', 'transcript', 'calendar_booking', 'prompt_writer', 'priority_support', 'advanced_analytics', 'crm_integration', 'sms_followups', 'custom_integrations', 'sla'],
        'feature_highlights' => [
            '4,000 included voice minutes each month',
            '10 live numbers and 20 assistants',
            'CRM integration, SMS follow-up, and rollout support',
            'Custom reporting, priority support, and expansion help',
        ],
        'badge_color' => 'amber',
        'description' => 'For multi-location teams that want tickIt wired into the rest of the business.',
        'recommended_for' => 'High-volume operators that need CRM and SMS workflows',
        'usage_copy' => 'Base fee plus €0.16 for each extra minute above the included 4,000.',
    ],
];
