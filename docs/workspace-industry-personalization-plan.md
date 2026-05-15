# Workspace Industry Personalization Plan

## Goal

Use the first workspace-use-case selection to shape the product around the customer's business type, so tickIt feels purpose-built instead of generic.

This plan focuses on:

- property management companies
- realty teams
- dental clinics
- IT support teams

The core idea is simple:

When a workspace is created, the business type and most common call use cases should decide what the customer sees, what the assistant defaults to, and how the workflow behaves inside the app.

## Product Principle

One platform should power all industries, but each workspace should feel tailored to the business that owns it.

That means the initial setup choice should affect:

- onboarding language
- assistant defaults
- prompt drafts
- intake fields
- case categories
- scheduling behavior
- dashboard examples
- docs and help content
- follow-up suggestions

## Workspace Profile Model

Add a structured workspace profile layer on top of the existing use-case flow.

Recommended workspace fields:

- `industry_key`
- `primary_use_case_key`
- `secondary_use_case_keys`
- `business_context_summary`
- `default_case_category_set`
- `default_contact_fields`
- `default_scheduling_mode`
- `default_prompt_template_key`
- `default_assistant_preset_key`
- `default_dashboard_pack_key`
- `docs_pack_key`

Recommended starter values:

- `property_management`
- `realty`
- `dental_clinic`
- `it_support`
- `other`

## Onboarding Experience

### Step 1

Ask:

`What kind of business are you using tickIt for?`

Options:

- Property management
- Realty
- Dental clinic
- IT support
- Other

### Step 2

Ask:

`What kinds of calls do you want to automate first?`

This second choice should be industry-specific.

### Property management call types

- Maintenance requests
- After-hours emergencies
- Tenant questions
- Leasing inquiries

### Realty call types

- New buyer leads
- Seller inquiries
- Showing requests
- Listing questions

### Dental clinic call types

- New patient booking
- Reschedule or cancellation
- Insurance or billing questions
- Dental emergency triage

### IT support call types

- New support issue
- Password or access problem
- Device or software issue
- Urgent outage or escalation

### Step 3

Create the workspace with the selected profile and immediately prefill:

- assistant name
- behavior preset
- first message
- prompt draft
- intake checklist
- case categories
- urgency logic
- follow-up behavior

## Shared Personalization Engine

Create a single configuration layer that maps each industry and call type to a preset pack.

Each pack should define:

- display copy
- onboarding helper text
- prompt template
- assistant behavior preset
- required intake fields
- recommended case labels
- recommended priority rules
- calendar behavior
- recommended docs articles
- dashboard empty states

Suggested class or service:

- `WorkspaceIndustryProfileCatalog`
- `WorkspaceIndustryProfileResolver`

## Property Management Experience

### Main product goal

Help property teams capture maintenance and tenant calls quickly, consistently, and with the right urgency.

### In-app tailoring

- Rename language toward maintenance and resident support
- Show default categories like plumbing, HVAC, electrical, appliance, pest, lockout, cosmetic
- Add required fields like property address, unit, access instructions, permission to enter, urgency
- Default dashboard widgets toward open maintenance issues, urgent requests, and pending visits
- Show docs focused on maintenance intake and after-hours call handling

### Assistant defaults

- behavior preset: `Steady Operator`
- calm, organized tone
- urgent-issue triage rules
- clear emergency detection
- strong contact recognition for repeat tenants

### Common use cases

- water leak
- no heat
- lockout
- appliance issue
- general tenant maintenance request

## Realty Experience

### Main product goal

Capture inbound leads and inquiry calls cleanly, then move them toward follow-up or scheduling.

### In-app tailoring

- Rename language toward leads, properties, showings, listings, buyers, sellers
- Default categories like buyer lead, seller lead, listing inquiry, showing request, callback needed
- Intake fields like name, preferred area, budget range, property address of interest, callback time
- Dashboard focused on fresh leads, callbacks due, and showing requests
- Docs focused on lead intake and showing coordination

### Assistant defaults

- behavior preset: `Bright Guide`
- energetic, warm, polished
- emphasis on lead quality and clean callback capture
- meeting-first follow-up after lead capture

### Common use cases

- buyer inquiry
- seller inquiry
- request a showing
- question about a listing

## Dental Clinic Experience

### Main product goal

Reduce front-desk load, book more appointments, and handle patient calls with warmth and clarity.

### In-app tailoring

- Rename language toward patients, appointments, hygienist, emergency visit, insurance
- Default categories like new patient, cleaning, reschedule, cancellation, emergency, billing question
- Intake fields like patient name, spelled full name, callback number, appointment preference, insurance notes
- Dashboard focused on appointment requests, urgent patient calls, and follow-up
- Docs focused on booking, rescheduling, and emergency call handling

### Assistant defaults

- behavior preset: `Premium Concierge`
- warm and reassuring tone
- reduced friction in scheduling language
- stronger familiarity for returning patients

### Common use cases

- new patient booking
- reschedule
- cancellation
- tooth pain or emergency visit
- insurance question

## IT Support Experience

### Main product goal

Turn support calls into clear, actionable tickets without making the caller repeat everything.

### In-app tailoring

- Rename language toward tickets, systems, devices, access, incidents, outages
- Default categories like password reset, device issue, software issue, connectivity, outage, escalation
- Intake fields like user name, team, device, affected system, urgency, number of impacted users
- Dashboard focused on urgent incidents, new tickets, and unresolved issues
- Docs focused on support intake and triage

### Assistant defaults

- behavior preset: `Confident Closer`
- fast, efficient, structured
- concise summaries
- strong case creation and escalation rules

### Common use cases

- login problem
- laptop issue
- printer issue
- software problem
- outage escalation

## UI Changes Across the App

The workspace profile should affect these areas automatically.

### Dashboard

- use industry-specific empty states
- show industry-relevant KPI labels
- suggest the next best setup step based on that vertical

### Assistant create view

- preload the right prompt draft
- preload the right greeting suggestion
- preload the right intake fields
- show industry-aware helper text

### Cases

- show industry-specific category options
- show industry-specific summary hints

### Contacts

- surface the most relevant fields first for that industry

### Calendar

- use industry-specific scheduling copy
- preconfigure whether scheduling is common or optional

### Docs

- route each workspace toward the right docs pack first

## Content Packs to Build

Each industry should have:

- one onboarding pack
- one prompt pack
- one assistant default pack
- one category pack
- one docs pack
- one dashboard pack

## Rollout Plan

### Phase 1

Add workspace industry profile fields and resolver logic.

### Phase 2

Use the profile during onboarding to prefill assistants, prompts, and workflow behavior.

### Phase 3

Update dashboard, case categories, and docs recommendations based on the profile.

### Phase 4

Add industry landing pages, docs packs, and polished demo flows for each vertical.

## Success Metrics

Track whether personalization improves:

- signup to first assistant created
- signup to first live call
- signup to first case created
- call-to-case completion rate
- prompt generation acceptance rate
- trial-to-paid conversion by industry

## Best First Bets

Prioritize in this order:

1. Property management
2. Dental clinic
3. IT support
4. Realty

Reason:

- property management and dental have very clear, repetitive phone workflows
- IT support is also strong, but usually needs a bit more system-specific language
- realty can work well, but lead quality and CRM handoff will matter more soon after launch

## Recommended Outcome

The final product should feel like:

- tickIt for property management
- tickIt for dental clinics
- tickIt for IT support
- tickIt for realty

without becoming four separate products.

The workspace profile should be the layer that makes the app feel specific, relevant, and easier to adopt from the first five minutes.
