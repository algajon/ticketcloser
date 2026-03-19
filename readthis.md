# Agentic UX/UI Upgrade Spec (Modern SaaS, Production Standard)

> **Purpose:** This document instructs an **agentic LLM** to audit, redesign, and iteratively improve the **end-to-end user experience** of our app to a **senior-tier** SaaS standard.  
> **Output quality bar:** Production-ready UX, consistent UI system, accessibility compliant, and implementable in small PRs.

---

## 0) Operating Principles (Non-Negotiables)

1. **Ship value fast, safely**
   - Prefer many small PRs over a big rewrite.
   - Every change must preserve or improve stability.
2. **Make the “happy path” effortless**
   - Reduce time-to-first-value (TTFV).
   - Fewer decisions upfront; progressive disclosure.
3. **Consistency beats novelty**
   - Create or extend a design system; no one-off UI.
4. **Accessibility is a feature**
   - Aim for WCAG 2.2 AA. Keyboard-first workflows must work.
5. **Be opinionated with defaults**
   - Pre-fill, suggest, and auto-configure whenever safe.
6. **Never expose secrets**
   - Backend-only credentials (e.g., private API keys) must never reach the client.

---

## 1) Agent Role & Responsibilities

You are the **Product UX Engineer + UI Systems Lead**.

You must:
- Map and optimize core user journeys.
- Produce an actionable UX backlog with impact/effort scores.
- Define a UI system (tokens + components + patterns).
- Implement improvements (or generate implementation-ready tickets/PR-ready diffs).
- Validate with usability heuristics, accessibility checks, and measurable success criteria.

---

## 2) Scope & North-Star Outcomes

### Primary outcomes (ranked)
1. **Reduce onboarding friction** (setup to “it works” in minutes)
2. **Increase user confidence** (clear states, diagnostics, confirmations)
3. **Reduce cognitive load** (clean IA, fewer screens, better defaults)
4. **Improve operational clarity** (logs, status, retry patterns, troubleshooting)
5. **Polish** (spacing, typography, microcopy, empty states, responsiveness)

### Key metrics (target examples)
- **TTFV:** first successful “core action” within 3–5 minutes
- **Onboarding completion rate:** +20–40%
- **Support tickets about setup:** -30%
- **Task success rate (usability test):** >90%
- **Accessibility:** no critical violations; keyboard navigation passes

---

## 3) Discovery Checklist (Do this first)

### 3.1 Inventory the product
Create a list of:
- Pages/routes
- Navigation structure
- Forms and workflows
- System messages/toasts/errors
- Data entities & relationships (workspaces, configs, cases, etc.)
- Roles/permissions and access boundaries

### 3.2 Identify the top 3 user journeys
Common examples for SaaS:
1. **Create workspace → configure integration → verify it works**
2. **Daily use:** view/manage primary objects (cases/tickets/requests)
3. **Troubleshoot & recover:** failed setup, retries, logs, status checks

For each journey, document:
- Steps
- Decision points
- Inputs required
- Failure points
- Where user trust breaks

### 3.3 UX audit (heuristic pass)
Score 1–5 each:
- Clarity of next action
- Visual hierarchy
- Form ergonomics
- Feedback & system status visibility
- Error prevention + recovery
- Consistency
- Accessibility
- Mobile responsiveness

Deliverable: **Audit summary + top 10 issues** with screenshots (or detailed notes).

---

## 4) UX Strategy: Patterns We Use Everywhere

### 4.1 Navigation & IA
- Use a clear top-level structure:
  - **Dashboard**
  - **Core objects** (e.g., Cases)
  - **Integrations/Automation**
  - **Workspace settings**
- Add persistent **workspace switcher** if multi-workspace is core.
- Avoid hiding critical setup in obscure settings.

### 4.2 Page structure
Every page should have:
- Title + 1-line description
- Primary CTA (one obvious “next”)
- Secondary actions in overflow menus
- A “status panel” if setup/operations related

### 4.3 Feedback rules
- Every async action needs:
  - Loading state (button spinner + disabled)
  - Success toast + inline confirmation where relevant
  - Failure toast + inline error with recovery options
- Use “optimistic UI” carefully; never hide failed states.

### 4.4 Forms (senior-tier ergonomics)
- Prefer fewer fields; group advanced options under “Advanced”
- Inline validation, not after submit
- Show examples and constraints (E.164, area codes, etc.)
- Save drafts automatically where safe
- Add “Test” buttons for integrations (smoke test)

### 4.5 Empty states
Every empty state must include:
- What this is
- Why it’s empty
- The single best next step (CTA)
- Optional docs link (if available)

### 4.6 Error handling & recovery
Standardize error UX:
- Friendly headline
- What happened (human language)
- What to do next (re-try, check config, contact support)
- Include a “Copy details” block (request id, timestamp, relevant ids)

---

## 5) Onboarding & Setup: The “Wizard” Standard

Convert multi-step setups into a guided flow:
- **Step 1:** Create/confirm workspace basics
- **Step 2:** Configure assistant/tool settings
- **Step 3:** Provision phone number
- **Step 4:** Verify (test call / test webhook)
- **Step 5:** Go live checklist

### Required wizard features
- Stepper UI with progress
- Persistent “Setup status”
- Ability to resume later
- Clear “Not ready / Ready / Needs attention” badges
- “Run test” actions (webhook verification, health checks)

### Verification UX (must-have)
Provide a “Verify Setup” panel:
- Webhook reachable? ✅/❌
- Auth headers configured? ✅/❌
- Tool/Assistant IDs saved? ✅/❌
- Phone number assigned? ✅/❌
- Latest test result timestamp
- “Re-run test” button

---

## 6) Operational UX (Trust & Control)

### 6.1 Activity feed / audit log
Users should see:
- Recent calls / events
- Tool invocations
- Case creation results
- Errors with replay/retry where safe

### 6.2 Status surfaces
- Workspace-level status: “Healthy / Degraded / Down”
- Integration-level status: tool, assistant, phone number
- Show last sync time, last successful event, last error

### 6.3 Troubleshooting mode
Add a dedicated troubleshooting view:
- “What’s configured”
- “What’s failing”
- “How to fix”
- Copy-paste technical details for support

---

## 7) UI System (Design Tokens + Components)

### 7.1 Visual tokens
Define tokens (even if implemented via Tailwind/CSS vars):
- Color: background/surface/text/border, semantic (success/warn/error/info)
- Typography scale (H1/H2/body/small)
- Spacing scale (4/8/12/16/24/32)
- Radius scale (8/12/16)
- Shadow scale (subtle → modal)
- Focus ring styles (high contrast)

### 7.2 Component library (minimum)
- Buttons (primary/secondary/tertiary/destructive)
- Inputs, selects, textareas, toggles
- Form field wrapper (label, hint, error)
- Toast notifications
- Cards/panels
- Badges/status chips
- Tabs
- Tables with empty/loading states
- Modals + confirmations
- Code/copy blocks

### 7.3 Interaction patterns
- Confirm destructive actions
- Provide “undo” where feasible
- Keyboard shortcuts where meaningful (later phase)
- Skeleton loaders for data-heavy views

---

## 8) Content Design (Microcopy that feels premium)

Rules:
- Short sentences
- Actionable labels (“Provision number” not “Submit”)
- Avoid blame language (“We couldn’t…” not “You failed…”)
- Always tell the user what happens next

Standard CTA language:
- “Save changes”
- “Sync with provider”
- “Provision number”
- “Verify setup”
- “View logs”
- “Copy details”

---

## 9) Accessibility Requirements (WCAG 2.2 AA)

Must pass:
- Keyboard navigation for all interactive elements
- Visible focus states
- Proper labels/ARIA for inputs and icons
- Color contrast minimums
- Form errors announced to screen readers
- No critical a11y violations (use automated checks + manual spot checks)

---

## 10) Implementation Workflow (Agentic Loop)

### Phase A — Diagnose
Deliverables:
1. **User journey map** (top 3)
2. **UX audit top 10**
3. **Backlog v1** with impact/effort score + rationale

### Phase B — Design
Deliverables:
1. IA proposal + nav changes (if needed)
2. Wireframes for key flows
3. UI system tokens + component spec

### Phase C — Build (small PRs)
Rules:
- Each PR includes:
  - Before/after screenshots or short notes
  - Accessibility notes
  - Rollback plan (if risky)
- Add feature flags only if required for safety.

### Phase D — Validate
- Run through the 3 journeys as a new user
- Check empty states & failure states
- Confirm error copy is actionable
- Confirm metrics instrumentation events exist (if analytics available)

### Phase E — Measure & Iterate
- Improve the top bottleneck each iteration

---

## 11) Prioritization Framework

Score each item (1–5):
- Impact on conversion/TTFV
- Impact on trust & clarity
- Frequency of use
- Effort
- Risk

Sort by: **(Impact + Trust + Frequency) / (Effort + Risk)**

---

## 12) Definition of Done (DoD)

A UX/UI change is “done” when:
- UI is consistent with design system
- All states exist: loading, empty, error, success
- Copy is clear + actionable
- Keyboard navigation works
- No new a11y critical issues
- The change improves a measured journey step or removes friction

---

## 13) Immediate High-Impact Targets (Start Here)

1. **Onboarding wizard + setup status panel**
2. **Verification & test tools** (health checks, last event, re-run)
3. **Better error UX** (copy details + recovery options)
4. **Activity feed/logs** for integration events
5. **UI system pass** (spacing, typography, buttons, form wrapper)

---

## 14) Output Format Requirements (Agent Deliverables)

Create these artifacts in-repo:
- `/docs/ux/audit.md` (findings + screenshots/notes)
- `/docs/ux/journeys.md` (top journeys)
- `/docs/ux/backlog.md` (prioritized list)
- `/docs/ux/design-system.md` (tokens + components + usage rules)
- `/docs/ux/release-notes.md` (user-facing summary per iteration)

If the agent can implement:
- Prefer PR-ready diffs
- Otherwise, generate issue tickets with acceptance criteria

---

## 15) Tone & Style (What “Senior SaaS” feels like)

- Calm, confident, minimal
- Fewer UI elements on screen, better hierarchy
- Helpful microcopy
- No visual clutter
- Predictable interactions
- Fast perceived performance

---

### Final instruction
**Do not stop at suggestions.** Produce:
1) a prioritized plan, then  
2) the UI system spec, then  
3) implementation-ready changes (or tickets) that can be shipped in small increments.