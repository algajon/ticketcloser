# Ticketcloser — Clean Handoff (Stable + Ready for Option A)

**Status:** ✅ Current Ticketcloser app is working and stable (no known bugs).  
**Now building next:** **Option A — Managed Vapi** (Ticketcloser owns Vapi org + API key, provisions assistants/tools/phone numbers per workspace, bills via credits).  
**Stack:** Laravel 11 + Blade + TailwindCSS + MariaDB (InnoDB).  
**Market:** US only (E.164 US phone numbers).

---

## 1) What exists today (baseline)

### Core product objects
- **Users** authenticate (standard Laravel auth).
- **Workspaces** exist (multi-tenant boundary).
- **Support Cases** (“tickets”) exist in DB and can be created via API.

### Working API endpoints (already implemented)
- `POST /api/webhooks/vapi`
  - Receives Vapi webhook events.
  - Handles `message.type == tool-calls` and returns tool results.
- `GET /api/cases` + `POST /api/cases`
  - Auth via headers:
    - `Authorization: Bearer tc_...` (workspace integration token)
    - `X-Workspace-Slug: <workspace_slug>`

### Working Vapi integration pattern (current)
- Vapi tool calls **hit Ticketcloser** and Ticketcloser creates a SupportCase and returns `{caseNumber}` as tool result.
- The “routing” to a workspace is done via `X-Workspace-Slug` + `Authorization` token.

### Web routes (already set up)
`routes/web.php` includes:
- onboarding flow pages
- workspace settings
- cases list/detail pages
- integrations page (token management)
- assistant settings page

---

## 2) What Option A means (Managed Vapi)

Ticketcloser will:
1) Store **one** Vapi API key in `.env` (our org).
2) For each workspace, automatically create/update in Vapi:
   - a **Tool** (`createCase`)
   - an **Assistant**
   - a **Phone Number**
3) Save the Vapi IDs in our DB so the workspace can manage them from our UI.
4) Track usage/cost and bill the workspace using **credits**.

---

## 3) Target user experience (MVP)

Workspace admin can:
1) **Create / configure assistant**
   - name, prompt template, voice selection
2) **Connect phone number**
   - “Provision a new Vapi number” (or paste an existing Vapi phone number id for now)
3) **Test**
   - call the number → assistant collects info → creates ticket in Ticketcloser → returns case number
4) **See usage**
   - call logs + cost → credits deducted

---

## 4) Data model to add (MVP)

Add these tables:

### `assistant_configs`
- `workspace_id`
- `name`
- `system_prompt`
- `voice_provider`, `voice_id`
- `vapi_tool_id`
- `vapi_assistant_id`
- `is_active`

### `workspace_phone_numbers`
- `workspace_id`
- `e164`
- `vapi_phone_number_id`
- `is_active`

(Keep it minimal now; later we can add tool library + multiple assistants per workspace.)

---

## 5) Services to implement (MVP)

### A) VapiClient
`app/Services/Vapi/VapiClient.php`
- wraps Vapi REST calls with `Authorization: Bearer <VAPI_API_KEY>`
- methods:
  - `createTool`, `updateTool`
  - `createAssistant`, `updateAssistant`
  - `createPhoneNumber`, `updatePhoneNumber`

### B) Provisioning service (idempotent)
`app/Services/Vapi/VapiProvisioningService.php`
- `provisionAssistantAndTool(Workspace $workspace, array $input): AssistantConfig`
  - ensure tool exists (server.url = `/api/webhooks/vapi`)
  - ensure assistant exists (toolIds include createCase tool)
  - store Vapi IDs
- `provisionPhoneNumber(Workspace $workspace, array $input): WorkspacePhoneNumber`
  - create/update phone number
  - attach assistantId
  - set phone number server to `/api/webhooks/vapi` (for call events)
  - store Vapi phone number id + e164

**Key rule:** these must be safe to run repeatedly without duplication.

---

## 6) UI pages to build next (Blade + Tailwind)

### 1) `onboarding.phone`
- “Provision phone number”
- shows:
  - status (connected or not)
  - number (E.164)
  - button: “Provision new number in Vapi”
  - button: “Sync configuration”

### 2) `onboarding.assistant`
- assistant name
- voice selection dropdown
- prompt editor textarea (with a default template)
- button: “Create/Update Assistant in Vapi”

### 3) Workspace settings equivalents
- `/app/workspaces/{slug}/assistant`
- `/app/workspaces/{slug}/phone-numbers`

Minimal, modern SaaS layout:
- left nav (Cases, Assistant, Phone Numbers, Integrations, Billing)
- cards, clear CTAs, copy-to-clipboard blocks

---

## 7) Prompt template requirement (for all businesses)
Assistant must:
- collect issue
- ask for **phone number linked to the account** if not already clear
- ask 1–2 clarifying questions only if necessary
- determine category + priority
- read back summary + confirm
- on confirmation call `createCase` with:
  - `title`, `description`, `category`, `priority`, `requesterPhone`, `externalCallId`

---

## 8) Credits (not built yet — next milestone after provisioning)
Add:
- `credit_transactions` (ledger)
- `usage_events` (per call)
Then:
- On Vapi `end-of-call-report`, store cost breakdown and deduct credits.

---

## 9) Exact next steps to resume in a new chat

1) Add `.env` + `config/services.php` for Vapi API key.
2) Create migrations + models:
   - `AssistantConfig`
   - `WorkspacePhoneNumber`
3) Implement `VapiClient`.
4) Implement `VapiProvisioningService` (assistant/tool first).
5) Add controller actions:
   - `VoiceAssistantController@update` calls provisioning
   - `VoiceAssistantController@storePhoneNumber` calls provisioning
6) Build the two onboarding Blade views:
   - `onboarding.phone`
   - `onboarding.assistant`
7) Test end-to-end:
   - provision assistant/tool/number
   - call number → ticket created in DB → assistant speaks case number

---

## 10) Non-negotiables
- US-only phone numbers (E.164 +1...)
- Never store real tokens in git
- Tool result must always be returned as a **string** in Vapi tool response
- Provisioning must be idempotent

