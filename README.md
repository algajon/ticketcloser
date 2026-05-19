# tickIt

tickIt is a Laravel 12 app for teams that still run on the phone.

It answers calls with voice assistants, turns those calls into structured tickets, stores transcripts and call history, keeps contacts organized, and can book follow-up meetings when the workflow calls for it.

Production URL: [ticketcloser.online](https://ticketcloser.online)

## Current Status

As of 2026-05-15, the active working branch is `codex/prod-release-2026-05-12`.

Recent shipped work includes:

- shared Vapi tool flow for ticket creation and meeting booking
- multilingual assistant support with curated Azure voices and localized prompts/opening lines
- German voice support through the Vapi + Azure path
- call language labeling across calls, tickets, and dashboards
- easier existing-number setup for US, German, UAE, and other international phone numbers
- safer imported-number binding when switching an assistant from an instant test number to a BYO number
- dashboard and app-shell cleanup across views
- production deploy flow on Hostinger with targeted file sync plus Laravel cache rebuilds

Current automated test snapshot on this branch:

- `118` tests passing
- `556` assertions
- PHPUnit doc-comment deprecation warnings still present in some older tests

## What The Product Does

tickIt is built around one main loop:

1. A caller reaches a workspace phone number.
2. A Vapi assistant answers using the workspace's selected language, prompt, voice, and workflow.
3. The assistant can look up caller context, create a ticket, and optionally book a follow-up meeting.
4. The app stores the call record, transcript metadata, contact links, ticket details, and scheduling state.
5. Workspace users manage the result through dashboards, tickets, contacts, calls, and calendar views.

## Main Product Areas

### Workspaces and onboarding

- Multi-workspace support with per-workspace defaults
- Workspace-level language, market, and phone-routing preferences
- Use-case-driven setup paths for assistants and workflows
- Plan-based limits for assistants, minutes, and phone numbers

### Voice assistants

- Multiple assistants per workspace
- Prompt, opening line, preset, model, and fallback transfer configuration
- Runtime caller recognition and case lookup support
- Localized prompt and opening-line translation through OpenAI when configured

### Phone numbers

- Instant tickIt/Vapi-hosted number provisioning for fast testing
- BYO carrier imports through Vapi credentials
- Forwarding-based setup for businesses that want to keep their existing public number
- Country-aware setup guidance for US/Canada, Germany, UAE, and other numbers
- Existing-number import flow now supports a much simpler assistant-first setup path

### Tickets and workflows

- Shared ticket creation service for voice-created cases
- Case status and workflow state updates
- Queue-aware ticketing and contact linking
- Transcript, recording, and call metadata attachment to tickets

### Calls and transcripts

- Call logs with transcripts, durations, and cost/usage tracking
- Language labeling for configured and detected call languages
- Recording and transcript plans managed through assistant payloads

### Contacts

- Contact search and profile pages
- Ticket and call linking to known contacts
- Caller recognition in runtime assistant flows

### Calendar and meetings

- Suggested events and follow-up booking
- Google Calendar connection flow
- Assistant-driven booking via a shared meeting service
- Safe fallback when live calendar booking is unavailable or fails

### Prompt writer

- Prompt generation and versioning
- Workspace-aware prompt drafts

### Billing and plans

- Free trial and paid plans
- Stripe-backed billing configuration
- Voice minute limits, overage tracking, and feature gating

## Language and Telephony Notes

tickIt currently supports a broad set of spoken assistant languages through the regional stack catalog, including:

- English (US and UK)
- Arabic
- Spanish
- French
- German
- Hindi
- Bengali
- Mandarin Chinese
- Portuguese (Brazil)
- Russian
- Urdu
- Indonesian
- Japanese
- Korean

German support is live through the Vapi + Azure voice path.

For phone numbers:

- Vapi-hosted instant numbers are primarily the fastest US testing path.
- Local and international numbers generally use the BYO import or forwarding path.
- German and other international business numbers are expected to come from an external carrier and then be attached through Vapi credentials.

## Tech Stack

- PHP 8.2+
- Laravel 12
- Blade
- Alpine.js
- Tailwind CSS
- Vite
- SQLite for local development by default, with support for other Laravel database drivers
- Vapi for assistant and telephony orchestration
- OpenAI for prompt and opening-line localization plus prompt-writer features
- Google Calendar OAuth for meeting booking
- Stripe for billing

## Repository Map

Important app areas:

- `app/Http/Controllers`
  - UI and webhook entry points
- `app/Services/Vapi`
  - assistant provisioning, phone-number provisioning, and Vapi API client logic
- `app/Services/Tickets`
  - shared ticket creation logic
- `app/Services/Meetings`
  - shared meeting booking logic
- `app/Services/Assistants`
  - assistant prompt/opening-line localization helpers
- `app/Support`
  - regional voice/telephony catalog and workspace use-case catalogs
- `resources/views`
  - Blade UI for dashboard, assistants, phone numbers, tickets, calls, contacts, calendar, billing, and onboarding
- `tests/Feature`
  - end-to-end feature coverage for voice, calendar, dashboard, onboarding, billing, and access flows
- `docs`
  - UX audits, backlog notes, commercialization planning, and workspace personalization plans

## Local Setup

### Quick start

```bash
composer run setup
```

That script installs PHP dependencies, creates `.env` if needed, generates the app key, runs migrations, installs frontend dependencies, and builds assets.

### Manual setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --force
npm install
npm run build
```

### Local development

```bash
composer run dev
```

That starts:

- the Laravel app server
- the queue listener
- Laravel Pail logs
- the Vite dev server

## Environment Variables

At minimum, local development needs the normal Laravel app and database settings. The external-service settings below become important as soon as you use live voice, prompt generation, billing, or calendar sync.

### App and database

- `APP_NAME`
- `APP_ENV`
- `APP_KEY`
- `APP_URL`
- `DB_CONNECTION`
- `DB_DATABASE`
- `DB_HOST`
- `DB_PORT`
- `DB_USERNAME`
- `DB_PASSWORD`

### Vapi

- `VAPI_API_KEY`
- `VAPI_BASE_URL`
- `VAPI_WEBHOOK_URL`
- `VAPI_WEBHOOK_SECRET`

### OpenAI

- `OPENAI_API_KEY`
- `OPENAI_BASE_URL`
- `OPENAI_MODEL`

### Google Calendar

- `GOOGLE_CLIENT_ID`
- `GOOGLE_CLIENT_SECRET`
- `GOOGLE_REDIRECT_URI`

### Stripe

- `STRIPE_KEY`
- `STRIPE_SECRET`
- `STRIPE_WEBHOOK_SECRET`
- `STRIPE_PRICE_STARTUP`
- `STRIPE_PRICE_PRO`
- `STRIPE_PRICE_ENTERPRISE`

### Misc

- `SERVER_API_TOKEN`
- normal Laravel mail, queue, cache, session, and filesystem settings

## Key Commands

### Run the full test suite

```bash
php artisan test
```

### Run focused voice and phone tests

```bash
php artisan test tests/Feature/PhoneNumbersPageTest.php
php artisan test tests/Feature/AssistantVoiceQualityTest.php tests/Feature/VapiWebhookTest.php
```

### Rebuild Blade caches

```bash
php artisan view:clear
php artisan view:cache
```

### Clear and rebuild app caches

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Production Deployment Notes

Production currently runs on Hostinger.

Important deployment characteristics:

- targeted file sync is used for quick app updates
- Laravel caches are rebuilt after deploy
- Node is not assumed to exist on the server, so frontend assets should be built locally before shipping any Vite output changes
- production secrets must stay outside the repo

Typical production follow-up commands:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

For view-only changes, the lighter deploy path is often enough:

```bash
php artisan view:clear
php artisan view:cache
```

## Documentation In This Repo

This README is intended to be the main operational reference for the repo.

The goal is to keep source control focused on product code, tests, and a single up-to-date project overview instead of accumulating temporary planning handoff files.

## Testing and Quality Notes

The repo has strong feature-test coverage around the main business flows:

- assistant provisioning
- Vapi webhook handling
- ticket creation
- meeting booking
- dashboard behavior
- phone-number setup
- onboarding and workspace access

There are still PHPUnit doc-comment metadata deprecation warnings in some older tests. They do not currently break the suite, but they should eventually be migrated to PHPUnit attributes.

## Security Notes

- Do not commit production credentials, SSH passwords, API keys, or `.env` files.
- Keep Vapi webhook verification enabled in production by setting `VAPI_WEBHOOK_SECRET`.
- Treat workspace integration tokens and server API tokens as secrets.

## GitHub Workflow

Remote:

- `ticketcloser` -> `https://github.com/algajon/ticketcloser`

Current active release branch at the time of this update:

- `codex/prod-release-2026-05-12`

When pushing app changes:

1. Run the relevant tests locally.
2. Update docs when the product surface changes.
3. Commit only meaningful repo state.
4. Push the working branch.
5. Deploy separately to production if needed.

## Summary

tickIt is no longer just a Laravel starter.

It is a multilingual voice-ops product that connects live phone calls to tickets, contacts, transcripts, and meetings, with production deployment already active and ongoing work centered on onboarding quality, phone-number flexibility, and vertical-specific workflows.
