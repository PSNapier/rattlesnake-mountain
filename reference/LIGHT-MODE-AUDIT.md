# Light Mode and Legibility Audit

Companion to ROADMAP `[012] Light Mode Only and Legibility Pass`.

The site is light mode only. There is no theme toggle, no appearance cookie, and
no `dark:` utility anywhere in the sources Tailwind compiles.

## What was removed

| Thing | Where it lived | Why |
| --- | --- | --- |
| `initializeTheme()` call | `resources/js/app.ts` | Added a `dark` class to `<html>` whenever the OS preferred dark |
| `useAppearance` composable | `resources/js/composables/useAppearance.ts` | Theme state, `localStorage` key, and the `appearance` cookie |
| `AppearanceTabs` component | `resources/js/components/AppearanceTabs.vue` | The light/dark/system switcher. Already unreferenced by any page |
| `HandleAppearance` middleware | `app/Http/Middleware/HandleAppearance.php` | Shared the `appearance` cookie into Blade |
| `appearance` cookie exemption | `bootstrap/app.php` | Nothing reads the cookie now |
| Commented dark-mode blocks | `resources/views/app.blade.php` | Dead inline script and stylesheet kept the intent ambiguous |
| 103 `dark:` utilities | 28 files under `resources/js` | See below |
| 59 `dark:` utilities | Published paginator views | See below |

## Why the `dark:` classes were not inert

`resources/css/app.css` had `@custom-variant dark (&:is(.dark *))` commented out,
which reads as "dark mode is already off". It is not. With no custom variant
registered, Tailwind v4 falls back to its built-in `dark:` variant, which
compiles to `@media (prefers-color-scheme: dark)`. Every `dark:` class was live
for any visitor whose operating system was set to dark, with no way to turn it
off, and against a palette that was never designed for it.

Two sources fed the compiled stylesheet beyond our own components:

- `@source '../../storage/framework/views/*.php'` pulled in Laravel's compiled
  **exception-renderer** Blade views, which carry their own dark theme and a
  theme switcher. Removed; those are framework debug pages, not app UI.
- `@source '.../Illuminate/Pagination/resources/views/*.blade.php'` pointed at the
  **vendor paginator**, whose Tailwind view ships dark variants. The paginator is
  real app UI (admin users, trades, shop), so the views were published to
  `resources/views/vendor/pagination/`, stripped, and the source glob repointed
  at the local copies.

After both, the built stylesheet contains zero `dark:` utilities and zero
`prefers-color-scheme` queries.

`tests/Feature/LightModeTest.php` fails if any of this comes back.

## Contrast findings

Ratios are WCAG 2.1, measured against the two surfaces the site actually uses:
the page background `cape-palliser-100` (`#ede3d8`) and card/box surfaces
(`cape-palliser-50`, effectively white). AA is 4.5:1 for body text and 3:1 for
large text (18.66px bold or 24px regular).

### Fixed

| Element | Was | Ratio on page | Now | Ratio on page |
| --- | --- | --- | --- | --- |
| `h2`, `h3` | `shakespeare-400` `#56b8db` | **1.79** — fails at any size | `shakespeare-700` `#1e5e80` | 5.59 — AA |
| `h4`, `h5` | `new-orleans-500` `#de9331` | **1.99** — fails at any size | `new-orleans-800` `#8a4c22` | 5.28 — AA |
| Body text | `cape-palliser-600` `#965d49` | 4.19 — just under AA | `cape-palliser-700` `#7e493f` | 5.69 — AA |
| Markdown links in `.box` | inherited body colour, no underline | not distinguishable from text | `shakespeare-700` + underline | 5.59 — AA |

### Accepted

| Element | Ratio | Reasoning |
| --- | --- | --- |
| `h1` `cape-palliser-500` on page | 3.44 | `text-5xl`, comfortably in the large-text band where AA is 3:1. Left alone to keep the display voice |
| White on `shakespeare-500` buttons | 3.64 | Button labels are `text-sm` semibold, so this is below AA for body text. Darkening the button fill changes the primary brand surface site-wide, which is a design decision rather than a bug fix. Flagged for the client, not changed here |

## Pages checked

Each renders and was reviewed against the palette above.

| Page | Route | Notes |
| --- | --- | --- |
| Home | `/` | Obsolete `@rattlesnakeadmin` DeviantArt CTA removed; grid now 2-up. News box reads from announcements `[007]` |
| Getting Started, Rules, Lore, and the other CMS pages | `/getting-started`, `/rules`, … | Rendered through `cms/Show` and `DynamicInfo`. Body copy and headings covered by the base rules above |
| Shop | `/shop` | Card surfaces; four `dark:` utilities removed |
| Leaderboard | `/leaderboard` | Fourteen `dark:` utilities removed, the heaviest single page |
| Dashboard | `/dashboard` | Eleven removed |
| Inventory | `/inventory` | Clean already |
| Trades | `/trades` | New in `[006]`, written light-only |
| Users list and profile | `/users`, `/u/{user}` | Seven and one removed |
| Settings: Profile, Password, Avatar | `/settings/*` | The "Appearance" tab is avatar-only now that the theme switcher is gone, so it is labelled **Avatar** |
| Auth: login, register, reset | `/login`, `/register`, … | Split and card layouts, one each removed |
| Admin | `/admin` | Index and Users tab cleaned |
| Dev password gate | `/dev-password` | Eight removed |
| Not found | fallback | Clean |

## Known gaps

- Contrast was computed from the palette tokens, not sampled from rendered
  screenshots. Components that stack translucent surfaces (dialog overlays at
  `bg-black/80`, hover states at `/50` opacity) are not covered by those numbers.
- The button-label ratio above is a real AA failure left in place deliberately.
  It needs a palette decision from the client.
