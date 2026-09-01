# Hando Admin Template Integration

The web app's visual design now comes from **Hando** (v1.0.0, Zoyothemes) — a
Bootstrap 5 + jQuery admin dashboard template the user supplied as a `.rar`
archive. This documents what was integrated, how, and what's left if you want
to take it further.

## What's actually wired in

- **Assets**: a selective copy of Hando's compiled `dist/assets/` lives at
  `apps/web/public/hando/assets/` — `app.min.css` + `icons.min.css` (Bootstrap
  5.3 + Hando's custom SCSS, Material Design Icons + Tabler Icons webfonts),
  and the JS libs actually used: jQuery, Bootstrap's bundle, SimpleBar,
  node-waves, Waypoints, jQuery.counterup, Feather Icons. The full template
  ships ~90MB of demo images/chart libraries/every icon family; only ~13MB
  made the cut. `apps/web/index.html` links/loads all of it.
- **`Layout.tsx`** (the app shell — topbar + sidebar + content area) is
  rebuilt from scratch using Hando's real `pages-starter.html` markup:
  `#app-layout`, `.topbar-custom`, `.app-sidebar-menu`/`#sidebar-menu`,
  `.content-page`. Nav items are React Router `NavLink`s styled as Hando's
  `.tp-link` (flat items — no nested collapsible groups, since our real nav
  is flat). The user dropdown replaces the old always-visible logout button
  with Hando's real Bootstrap dropdown pattern.
- **`LoginPage.tsx`** rebuilt using Hando's `auth-login.html` card layout
  (minus the fake social-login buttons and testimonial carousel — not real
  content for this app).
- **`DashboardPage.tsx`** rebuilt using Hando's real stat-widget markup
  (`.card > .card-body > .widget-first`, colored icon-circle + big number).
- **Every other page** (~28 files: employee list/detail, time off, ATS,
  admin sections, reports, onboarding, etc.) was **not** rewritten — see
  "The compat-layer approach" below.

## The compat-layer approach

Rewriting all ~28 remaining page components to Hando's real Bootstrap markup
(nested `.card-body`, `.nav-tabs`, `.form-control` on every input) would have
meant touching nearly every file in the app in one pass — a lot of surface
area to get right and re-verify. Instead, `apps/web/src/styles.css` was
rewritten (not appended to) so the **existing** semantic class names those
components already use (`.card`, `.badge`/`.badge-active`/`.badge-pending`/
etc., `.data-table`, `.filter-bar`, `.tabs`/`.tab`, `.form-grid`,
`.stat-grid`, `.bulk-action-bar`, bare `<button>`/`<input>`/`<select>`) are
redefined using Hando's actual design tokens — its real CSS custom properties
(`--bs-primary`, `--bs-border-color`, `--bs-success-bg-subtle`, etc.) pulled
from `app.min.css`, not invented values. Zero JSX changes needed in those 28
files; the visual language changed underneath them.

**One real bug this produced and fixed**: a blanket `button:hover { background:
... }` rule has *higher* CSS specificity than a plain `.tab { background: none
}` rule, because pseudo-classes count toward specificity the same as a real
class. That made tab buttons render as solid blue blocks (label invisible) on
hover. Fixed by wrapping every blanket bare-button rule in `:where(...)`,
which contributes zero specificity — any class-based rule (ours or
Bootstrap's own `.nav-link`/`.tp-link`/`.dropdown-toggle`) now always wins,
regardless of pseudo-classes or rule order. If you add more blanket
element-selector rules to this stylesheet, keep using `:where()` for the same
reason.

If you want to convert one of the remaining pages to native Hando markup
later (nested `.card-header`/`.card-body`, real `.nav-tabs`, `.table
table-centered`), do it file by file — the compat classes keep working for
everything you haven't converted yet, so there's no cliff.

## `app.js` is deliberately not loaded

Hando's own `assets/js/app.js` calls `(new App).init()` **synchronously at
parse time** — before this is a problem, note that it's a plain `<script>`
tag executing in document order, and it sits *before* our `<script
type="module" src="/src/main.tsx">` in `index.html`. Module scripts are
deferred by spec (they run after the document parses, right before
`DOMContentLoaded`), so `app.js` runs first, while `#root` is still empty.
Its `initMenu()`/`initComponents()` query for `.button-toggle-menu`,
`#side-menu`, `[data-feather]`, etc. — none of which exist yet, so it's a
silent no-op for everything React renders.

What actually needed app.js's behavior is reimplemented directly in
`Layout.tsx`:
- **Feather icon replacement** (`window.feather.replace()`) runs in a
  `useEffect` on route change, plus again wherever a page's own icons render
  after an async fetch resolves (see `DashboardPage.tsx` — its widget icons
  don't exist in the DOM until `summary` loads, so Layout's route-level call
  alone would miss them).
- **Sidebar toggle** is plain React state (`sidebarHidden`) toggling
  `data-sidebar` on `document.body`.
- **Active nav highlighting** uses React Router's `NavLink` (automatic),
  not Hando's URL-string-matching jQuery.

Bootstrap's own `data-bs-toggle="dropdown"`/`"collapse"` data-api needs none
of this — Bootstrap 5 registers **delegated** listeners on `document` itself
when `bootstrap.bundle.min.js` loads, so it works regardless of whether the
target elements exist yet at that moment.

`head.js` (the light/dark theme toggle) *is* still loaded and needs no
changes — it waits for the `load` event, which fires well after React has
mounted.

## Known gaps / not done

- **Responsive/mobile breakpoints**: Hando's own CSS has a `max-width:
  991.98px` breakpoint that expects a hamburger-triggered overlay sidebar
  (a `.sidebar-enable` class toggle, off-canvas below ~992px) — that part of
  app.js's `initMenu()` wasn't reimplemented, since this is an internal HR
  admin tool mostly used at desktop widths. Below ~992px the sidebar and
  content currently just overlap. Worth fixing if tablet use turns out to
  matter.
- **Real Bootstrap component structure** on the 28 un-converted pages (see
  above) — they get Hando's colors/type/radii/shadows, not its exact
  markup patterns (nested card-body, real nav-tabs ARIA wiring, etc.).
- **Waves ripple click effect** and a few other cosmetic touches from
  `app.js` (fullscreen toggle, popover/tooltip auto-init) aren't wired up —
  low value for the effort given the timing problem above.
- Mobile app (`apps/mobile`, React Native) is untouched — this is a web-only
  visual change.

## Testing

Two real E2E regressions came out of this (both fixed, not worked around):

1. `helpers.ts`'s `loginAs()`/`logout()` assumed a plain always-visible
   "Log out" button — Hando's real pattern puts it inside a Bootstrap
   dropdown menu (`display:none` until the account-menu toggle is clicked).
   Fixed by giving the toggle a stable `aria-label="Account menu"` and
   updating the helpers to open it first.
2. The sidebar's "Hiring (ATS)" label shortened to "Hiring" (matches Hando's
   single-word nav-label convention) — `role-boundaries.spec.ts` had the old
   full label hardcoded.
3. `dashboard-and-reports.spec.ts` targeted the old `.stat-card`/`.stat-value`
   classes, which no longer exist since `DashboardPage.tsx` was fully
   rebuilt (not just re-styled via the compat layer) — updated to target the
   real widget markup.

Full suite (`npm run test:regression`) is green: 63 Pest, 1 Vitest, 25
Playwright.
