# Design System — Ticketcloser

## Stack
Tailwind CSS v3 + custom CSS variables for semantic tokens. Components implemented as Blade components or Blade partials.

---

## Color Tokens

| Token | Value | Usage |
|---|---|---|
| `--tc-bg` | `#f8fafc` (slate-50) | Page background |
| `--tc-surface` | `#ffffff` | Cards, panels |
| `--tc-border` | `#e2e8f0` (slate-200) | Card borders, dividers |
| `--tc-text` | `#0f172a` (slate-900) | Body text |
| `--tc-muted` | `#64748b` (slate-500) | Labels, hints |
| `--tc-accent` | `#0f172a` | Primary action |
| `--tc-success` | `#059669` | Success states |
| `--tc-warn` | `#d97706` | Warning states |
| `--tc-error` | `#dc2626` | Error states |
| `--tc-info` | `#2563eb` | Info states |

---

## Typography Scale

| Role | Class | Notes |
|---|---|---|
| H1 | `text-2xl font-semibold tracking-tight` | Page title |
| H2 / Section | `text-base font-semibold` | Card headers |
| Body | `text-sm text-slate-700` | General content |
| Label | `text-sm font-medium text-slate-900` | Form labels |
| Hint | `text-xs text-slate-500` | Field hints |
| Mono | `font-mono text-xs` | IDs, tokens, code |

---

## Spacing Scale

Use multiples of 4px via Tailwind: `p-1 p-2 p-3 p-4 p-5 p-6 p-8`.
- **Card padding:** `p-6`
- **Form field gap:** `space-y-5`
- **Section gap:** `space-y-4` or `gap-4`

---

## Radius Scale

| Usage | Class |
|---|---|
| Buttons, inputs | `rounded-xl` |
| Cards | `rounded-2xl` |
| Badges / chips | `rounded-full` |
| Small tags | `rounded-md` |

---

## Shadow Scale

| Usage | Class |
|---|---|
| Cards | `shadow-sm` |
| Modals | `shadow-xl` |
| Dropdowns | `shadow-lg` |

---

## Components

### Button — Primary
```html
<button class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-300 disabled:opacity-40 disabled:cursor-not-allowed">
```

### Button — Secondary
```html
<button class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-300">
```

### Button — Destructive
```html
<button class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-red-600 text-white text-sm font-medium hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-300">
```

### Form Field Wrapper
```html
<div>
  <label class="block text-sm font-medium text-slate-900">Label</label>
  <p class="text-xs text-slate-500 mt-0.5">Hint text</p>
  <input class="mt-2 w-full rounded-xl border-slate-300 focus:border-slate-400 focus:ring-slate-200 text-sm">
  <!-- @error('field') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror -->
</div>
```

### Badge / Status Chip
```html
<!-- Status: new -->
<span class="text-[11px] px-2 py-0.5 rounded-full border border-sky-200 bg-sky-50 text-sky-700">new</span>
<!-- resolved -->
<span class="text-[11px] px-2 py-0.5 rounded-full border border-emerald-200 bg-emerald-50 text-emerald-700">resolved</span>
<!-- error -->
<span class="text-[11px] px-2 py-0.5 rounded-full border border-red-200 bg-red-50 text-red-700">error</span>
```

### Status indicator (setup health)
```html
<div class="flex items-center gap-2 text-sm">
  <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Configured
</div>
<div class="flex items-center gap-2 text-sm">
  <span class="w-2 h-2 rounded-full bg-amber-400"></span> Not set
</div>
<div class="flex items-center gap-2 text-sm">
  <span class="w-2 h-2 rounded-full bg-red-500"></span> Error
</div>
```

### Toast (Alpine.js)
```html
<div x-data="{ toasts: [] }" @toast.window="toasts.push($event.detail); setTimeout(() => toasts.shift(), 4000)" class="fixed bottom-4 right-4 z-50 space-y-2">
  <template x-for="t in toasts" :key="t.id">
    <div class="flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg text-sm font-medium text-white"
         :class="t.type === 'success' ? 'bg-slate-900' : 'bg-red-600'">
      <span x-text="t.message"></span>
    </div>
  </template>
</div>
```

### Empty state
```html
<div class="py-12 text-center">
  <div class="text-2xl">🗂️</div>
  <div class="mt-3 text-base font-semibold">No items yet</div>
  <p class="text-sm text-slate-500 mt-1">Why it's empty.</p>
  <a href="..." class="mt-4 inline-flex px-4 py-2 rounded-xl bg-slate-900 text-white text-sm hover:bg-slate-800">Next step →</a>
</div>
```

### Code/copy block
```html
<div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">
  <code class="text-xs font-mono flex-1 truncate text-slate-700" x-text="value"></code>
  <button @click="navigator.clipboard.writeText(value)" class="text-slate-400 hover:text-slate-700 text-xs">Copy</button>
</div>
```

---

## Interaction Patterns

- **Loading state:** Add `wire:loading` or Alpine `x-bind:disabled="loading"` + spinner SVG + `disabled:opacity-40`
- **Confirm destructive:** Use `onclick="return confirm('...')"` for now; upgrade to modal later
- **Active nav:** `aria-current="page"` + `bg-slate-100 font-medium` class on the current nav link

---

## Accessibility Rules

- Every `<input>` must have a `<label for="...">` or `aria-label`
- All icon-only buttons must have `aria-label`
- Focus styles: never `outline-none` without a replacement `focus:ring-*`
- Error messages must use `role="alert"` or be associated via `aria-describedby`
- Add `<a href="#main-content">Skip to content</a>` as first child of `<body>`
