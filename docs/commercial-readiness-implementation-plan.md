# tickIt Commercial Readiness Implementation Plan

## Purpose

This document turns the current product direction into a practical implementation plan for making tickIt commercially ready.

The goal is to make the product:

- easier to understand
- easier to onboard
- more dependable in real customer workflows
- more trustworthy for businesses
- more ready for demos, pilots, and paid adoption

## Product Positioning

### Core message

- Never miss another call
- Calls turn into cases, contacts, and follow-up
- Built for teams that still run on the phone

### What the product should communicate

tickIt should feel like a product built to fully automate business phone calls without losing the human details that matter.

It should be easy for a customer to understand that tickIt:

- answers calls
- captures the important details
- creates the case
- saves the transcript
- tracks the contact
- keeps follow-up moving

## Primary Objective For The Next 30 Days

Improve trust, clarity, onboarding, and day-to-day usability enough that the app is ready for:

- polished demos
- early pilot customers
- community sharing
- founder-led sales conversations

## Workstream 1: Make The First 10 Minutes Feel Magical

### Goal

A new user should be able to go from signup to a successful test call with almost no confusion.

### Product changes

#### 1. Add a single onboarding question up front

Prompt:

`What are you using tickIt for?`

Setup paths:

- Property management / maintenance
- Front desk / receptionist
- IT support
- General customer support

#### 2. Pre-fill the workspace based on the selected path

Each path should automatically set:

- assistant personality
- prompt draft
- call flow
- intake fields
- case labels
- follow-up behavior

#### 3. Replace vague onboarding with a setup checklist

Checklist items:

- Create assistant
- Provision number
- Connect calendar
- Make test call
- Review first case

Each item should have:

- a clear status
- one obvious CTA
- a short explanation

#### 4. Add a strong onboarding completion state

Success state:

- `Your assistant is live`
- `Make a test call`

### Implementation tasks

- Add onboarding use-case selection screen
- Create use-case configuration presets
- Auto-apply defaults on workspace setup
- Build checklist UI and status rules
- Add `Your assistant is live` end-state screen
- Add test-call CTA and tracking

### Success criteria

- A new user can create an assistant in under 10 minutes
- A new user can make a successful test call without reading docs
- Onboarding completion rate improves

## Workstream 2: Remove The Things That Make Businesses Nervous

### Goal

Make the product feel dependable enough for a business to trust it with real inbound calls.

### Reliability requirements

Contacts, calls, transcripts, cases, and meetings must behave predictably and be easy to verify.

### Required fallback rules

- If a meeting is requested before a case exists, create the case first
- If booking fails, create a pending follow-up
- If caller data is unclear, ask again cleanly

### Auditability requirements

Every important record should make it obvious:

- why a case was created
- which call created it
- which assistant handled it
- when a meeting was attempted
- when a meeting was booked

### Admin controls

Add controls for:

- pause assistant
- disable number
- force human fallback
- replay last failed sync

### Visible status indicators

Show these clearly in the UI:

- number active / inactive
- calendar connected / not connected
- assistant synced / out of date

### Implementation tasks

- Audit and harden case/call/contact/meeting linking
- Add explicit fallback logic for edge cases
- Add event/audit timeline for case creation and booking attempts
- Add assistant and number operational controls
- Add connection and sync status badges

### Success criteria

- Support team can explain exactly what happened on any call
- Failed bookings never disappear silently
- Users can tell whether a number or integration is working at a glance

## Workstream 3: Make Daily Use Feel Calmer

### Goal

Reduce mental load for teams who use tickIt every day.

### Dashboard improvements

Build a true command center showing:

- missed opportunities
- recent cases
- recent calls
- meetings needing review
- lines that are inactive

### Contact experience

Every contact should have a unified timeline with:

- calls
- transcripts
- cases
- meetings
- notes

### Case detail improvements

Every case page should answer three questions immediately:

- what happened
- who is this
- what should happen next

### Saved views

Add saved views for:

- urgent
- needs callback
- pending meeting
- unresolved today

### Implementation tasks

- Rework dashboard into a daily operations view
- Build contact timeline UI and supporting queries
- Redesign case detail hierarchy
- Add saved filters/views for common workflows

### Success criteria

- Users can find the next important item in seconds
- Contact history feels complete and easy to scan
- Case pages require less clicking to understand

## Workstream 4: Make The Product Easier To Understand

### Goal

Replace technical language with plain, customer-facing language.

### Language rules

Avoid:

- provisioning
- artifacts
- sync payload
- voice pipeline

Prefer:

- connect your number
- save the call
- send follow-up
- book a meeting

### UX improvements

- Add helper text under risky settings
- Show examples next to inputs
- Build use-case templates instead of blank states

Example helper copy:

`Ask for the tenant's full name, unit number, and issue`

### Implementation tasks

- Rewrite high-traffic product copy
- Add helper text to risky forms
- Replace blank flows with guided templates
- Review all settings labels for clarity

### Success criteria

- A non-technical office user can understand the UI without training
- Important settings feel self-explanatory

## Workstream 5: Make The Product Community-Friendly

### Goal

Make tickIt easier to share, learn, and adopt.

### Public help center

Create guides for:

- how to set up a maintenance assistant
- how to route calls after hours
- how to connect Google Calendar
- how to write a better assistant prompt

### Publish example templates

Start with:

- maintenance intake
- leasing inquiries
- receptionist assistant
- IT helpdesk intake

### Feedback and community loop

Add:

- `Was this call handled well?`
- `Did the assistant miss anything?`
- public changelog
- public roadmap
- early adopter channel

Possible channels:

- email list
- Discord
- private customer group

### Implementation tasks

- Build lightweight help center structure
- Publish example templates
- Add in-app call feedback
- Create changelog and roadmap pages
- Set up early adopter communication channel

### Success criteria

- Early users can self-serve common setup tasks
- Feedback comes back through the product, not only through DMs

## Workstream 6: Add The Commercial Layer

### Goal

Make the product feel credible, fair, and ready to pay for.

### Required commercial pieces

- clear usage-based billing
- strong free-tier limits that cannot leak
- role permissions that match real teams
- CRM/integration roadmap with clear plan boundaries
- data retention controls
- export options
- privacy/security page
- simple SLA/support expectations
- demo workspace for sales calls

### Implementation tasks

- Clean up billing logic and usage reporting
- Harden plan enforcement
- Finish role-based access model
- Add privacy/security and retention pages
- Add export tools
- Add demo workspace seeded with realistic data

### Success criteria

- Pricing feels understandable
- Free-tier abuse is controlled
- Buyers can see a clear path from trial to paid

## 30-Day Prioritization

### Phase 1: Reliability and trust

Priority:

- reliability pass on calls, contacts, meetings, and assistant behavior

Deliverables:

- hardened fallback rules
- better contact/case/call/meeting linking
- visible sync and connection status
- admin controls for pause/disable/replay

### Phase 2: Fast onboarding

Priority:

- use-case onboarding templates

Deliverables:

- onboarding use-case selector
- default prompt/call-flow/intake presets
- setup checklist
- live/test-call success state

### Phase 3: Daily operations UX

Priority:

- contact timeline + clearer case detail flow

Deliverables:

- command-center dashboard
- contact timeline
- improved case detail structure
- saved views

### Phase 4: Trust layer

Priority:

- permissions, audit trail, status indicators, limits

Deliverables:

- stronger role permissions
- case and booking audit events
- line/integration status
- free-tier enforcement review

### Phase 5: Community and self-serve growth

Priority:

- help center + public templates + changelog

Deliverables:

- public docs
- example assistant templates
- in-app feedback loop
- changelog and roadmap

### Phase 6: Commercial polish

Priority:

- pricing and upgrade path that feel simple and fair

Deliverables:

- improved pricing UX
- clearer feature boundaries by plan
- privacy/security page
- demo workspace for sales

## Must-Have Before Selling

- stable case/call/contact/meeting relationships
- clean fallback behavior for scheduling and missing caller data
- onboarding templates
- operational status indicators
- role-based permissions
- usage-based billing visibility
- free-tier enforcement that cannot be bypassed
- clearer product copy

## Should-Have For Onboarding

- setup checklist
- test-call guided flow
- contact timeline
- command-center dashboard
- public setup guides
- example templates by use case

## Nice-To-Have For Growth

- public roadmap
- changelog
- early adopter community
- demo workspace
- export tools
- data retention controls

## Recommended Messaging To Keep

- Never miss another call
- Calls turn into cases, contacts, and follow-up
- Built for teams that still run on the phone

## Final Note

The biggest commercial unlock is not adding more raw AI capability.

It is making tickIt feel:

- trustworthy
- simple
- predictable
- easy to adopt

The app should help customers feel that they can plug it into a real phone workflow and trust it to keep work moving.
