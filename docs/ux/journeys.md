# UX Journeys — Ticketcloser

## Journey 1: New User — Workspace Setup to First Ticket

**Goal:** Go from signup → configured voice assistant → first real ticket in < 5 min.

| Step | Path | Decision point | Failure risk |
|---|---|---|---|
| 1 | Register | Pick email/pw | Low |
| 2 | Onboarding: Company | Name, slug, timezone, label | Medium — slug format confusion |
| 3 | Onboarding: Billing | Credits, payment | High — blocks progress if no credits |
| 4 | Onboarding: Voice | Provider, voice id, system prompt | High — 3 technical fields with no guidance |
| 5 | Onboarding: Intake | Greeting, hours | Low |
| 6 | Onboarding: Test | Manual test call setup | **Critical** — no inline status |
| 7 | Assistant page | Save & sync → Vapi IDs appear | High — no feedback on failure |
| 8 | Phone Numbers | Provision → E.164 appears | High — no error if no assistant yet |
| 9 | First call → ticket created | Appears in Cases inbox | Medium |

**Trust breaks:**
- Voice step: `voice_provider` and `voice_id` fields have no guidance or examples
- Test step: user cannot tell if webhook is reachable
- Phone provision: silent fail if assistant not yet synced

---

## Journey 2: Daily Use — View & Manage Cases

| Step | Path | Decision point | Failure risk |
|---|---|---|---|
| 1 | Login → Dashboard | See latest ticket, credits | Low |
| 2 | Cases inbox | Filter by status/search | Low |
| 3 | Open case | See details, activity, requester | Medium — no status change UI |
| 4 | Update status | — | **High** — missing from ticket show UI |
| 5 | See call ID | External call ID in Integration card | Low |

**Trust breaks:**
- No status-change control on the case detail page
- No assignee, no comment thread with user, no due date

---

## Journey 3: Troubleshoot — Something isn't working

| Step | Path | Decision point | Failure risk |
|---|---|---|---|
| 1 | Call doesn't create ticket | — | **Critical** — no log surface |
| 2 | Check integration page | See token | Medium — no webhook status displayed |
| 3 | Check assistant page | Vapi IDs shown | Medium — but no "test" button |
| 4 | Re-provision phone | Guess at what's wrong | **High** — no diagnostic copy or error display |
| 5 | Contact support | No support link | Low |

**Trust breaks:**
- Zero logs or event feed visible in the UI
- No health check / webhook ping tool
- Error states not surfaced (provisioning failures are silent)
