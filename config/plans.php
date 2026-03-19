<?php

/**
 * Plan definitions for TicketCloser.
 *
 * Limits of -1 = unlimited.
 */
return [
    'free' => [
        'label' => 'Free Trial',
        'price_monthly' => 0,
        'stripe_price_env' => null,
        'max_minutes' => 10,
        'max_assistants' => 1,
        'max_phone_numbers' => 0,
        'max_cases' => 25,
        'features' => ['basic_intake', 'transcript'],
        'badge_color' => 'slate',
        'description' => 'Try TicketCloser with a handful of calls before committing.',
    ],

    'startup' => [
        'label' => 'Startup',
        'price_monthly' => 49,
        'stripe_price_env' => 'STRIPE_PRICE_STARTUP',
        'max_minutes' => 500,
        'max_assistants' => 3,
        'max_phone_numbers' => 2,
        'max_cases' => -1,
        'features' => ['basic_intake', 'transcript', 'calendar_booking', 'prompt_writer'],
        'badge_color' => 'blue',
        'description' => 'For small support teams getting started with AI voice agents.',
    ],

    'pro' => [
        'label' => 'Pro',
        'price_monthly' => 149,
        'stripe_price_env' => 'STRIPE_PRICE_PRO',
        'max_minutes' => 2000,
        'max_assistants' => 10,
        'max_phone_numbers' => 5,
        'max_cases' => -1,
        'features' => ['basic_intake', 'transcript', 'calendar_booking', 'prompt_writer', 'priority_support', 'advanced_analytics'],
        'badge_color' => 'indigo',
        'description' => 'For growing teams that need more capacity and premium features.',
    ],

    'enterprise' => [
        'label' => 'Enterprise',
        'price_monthly' => 499,
        'stripe_price_env' => 'STRIPE_PRICE_ENTERPRISE',
        'max_minutes' => -1,
        'max_assistants' => -1,
        'max_phone_numbers' => -1,
        'max_cases' => -1,
        'features' => ['basic_intake', 'transcript', 'calendar_booking', 'prompt_writer', 'priority_support', 'advanced_analytics', 'dedicated_account_manager', 'custom_integrations', 'sla'],
        'badge_color' => 'amber',
        'description' => 'Unlimited everything with dedicated support and custom integrations.',
    ],
];
