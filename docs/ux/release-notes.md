# Release Notes — UX/UI Upgrade

## v1.1 — 2026-02-20

### ✨ New features

- **Setup checklist on Dashboard** — a smart checklist shows exactly what's left to configure (workspace → assistant → phone number), with a context-aware CTA button pointing to the next step. Disappears once all steps are done.
- **Status-change on case detail** — agents can now move a case through its lifecycle (new → triaged → in_progress → waiting → resolved → closed) directly from the case detail page.
- **Integrations page** — new page showing your integration token (copy-to-clipboard), an API reference code snippet, and the Vapi webhook URL. Regenerating the token now requires confirmation and shows a loading state.
- **Mobile navigation** — the sidebar is now accessible on phones via a hamburger button; a slide-in drawer with a close button and backdrop overlay.

### 🔧 Improvements

- **Toast notifications** — success and error messages now appear as floating toasts in the bottom-right corner instead of inline flash banners.
- **Voice provider dropdown** — the Voice Provider field on the Assistant setup page is now a dropdown (Play.ht, ElevenLabs, Azure, OpenAI) instead of a blank text box.
- **Setup health chips** — the Assistant page now shows the Vapi tool ID, assistant ID, and phone number in a row of status chips, so you can see at a glance what's synced.
- **Loading states** — "Save & Sync" and "Provision number" buttons now show a spinner and are disabled during submission so double-submits are impossible.
- **Colour-coded case badges** — status and priority chips on the case list and case detail now use distinct colours for each value (sky = new, emerald = resolved, red = critical, etc.).
- **Active navigation** — the sidebar now highlights the current page.

### ♿ Accessibility

- Skip-to-content link for keyboard users.
- Visible focus rings on all interactive elements (`outline: 2px solid indigo` via `focus-visible`).
- `aria-current="page"` on the active nav link.
- All form inputs now have proper `<label for="">` associations.
- Destructive actions (token regenerate) require an explicit confirmation.
