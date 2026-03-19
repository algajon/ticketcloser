# UX Audit — Ticketcloser

*Scored 1 (poor) → 5 (excellent)*

| Heuristic | Score | Notes |
|---|---|---|
| Clarity of next action | 3 | Onboarding has a clear Continue button but no "what's left" summary |
| Visual hierarchy | 3 | Cards and spacing are consistent but no typographic scale; all text same weight |
| Form ergonomics | 3 | Good inline errors but no inline validation, no field hints on Voice step |
| Feedback & system status | 2 | No loading states, success/error toasts missing on Assistant/Phone pages |
| Error prevention + recovery | 2 | Phone provision can silently fail; no webhook status anywhere |
| Consistency | 4 | Mostly consistent button styles and card patterns |
| Accessibility | 2 | No focus ring on nav links, no aria-labels on icon actions, no skip-link |
| Mobile responsiveness | 2 | Sidebar hidden on mobile with no hamburger; entire nav inaccessible on phone |

---

## Top 10 Issues (Impact · Effort)

| # | Issue | Impact | Effort | Priority |
|---|---|---|---|---|
| 1 | **No loading state on Vapi provisioning buttons** — user clicks "Save & Sync" and sees nothing for 2-5s, clicks again, double-provisions | Critical | Low | P0 |
| 2 | **No success/error toast on Assistant & Phone pages** — currently only flash messages, no JavaScript feedback | Critical | Low | P0 |
| 3 | **Voice step has bare text inputs for `voice_provider`/`voice_id`** — no examples, no dropdown, user likely to leave blank or typo | High | Low | P0 |
| 4 | **No mobile sidebar / hamburger** — entire nav inaccessible on small screens | High | Medium | P1 |
| 5 | **No setup status panel** — user cannot see if assistant, phone, and webhook are configured from one place | High | Medium | P1 |
| 6 | **No status-change UI on ticket detail** — agent cannot move a ticket from `new` → `in_progress` etc | High | Low | P1 |
| 7 | **Sidebar has no active/current state** — selected page not indicated | Medium | Low | P1 |
| 8 | **No activity/log feed for Vapi events** — user cannot debug failed calls | High | Medium | P2 |
| 9 | **No integration health check / webhook ping** — user cannot verify configuration is working | High | Medium | P2 |
| 10 | **No skip-to-content link or keyboard focus styles** — fails basic a11y | Medium | Low | P2 |
