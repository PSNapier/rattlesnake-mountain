# Browser Audit — Rattlesnake Mountain

**Date:** 2026-08-28
**Method:** Playwright MCP against `https://rattlesnake-mountain.test`, headless Chrome, 1440x900 (plus a 390x844 mobile pass). Three read-only agent passes: public/auth/static pages, game pages, admin plus guest plus mobile.
**Scope:** Rendering, console errors, network failures, links, layout, accessibility. No forms submitted, no admin actions triggered, no data written. The one exception was logout, used to reach the guest view.

Findings below are reported against the working tree on `main` at commit `aa06987`. Source-level claims were verified in the repo after the browser passes.

---

## High

### 1. `/admin/items` is a dead route

Navigating to `/admin/items` renders a blank page. Console:

```
Failed to load resource: the server responded with a status of 404 () @ .../resources/js/pages/admin/Items.vue:0
Error: Page not found: ./pages/admin/Items.vue at resolvePage
```

`ItemController@items` returns `Inertia::render('admin/Items', ...)` (`app/Http/Controllers/Admin/ItemController.php:24`), but no `resources/js/pages/admin/Items.vue` exists. The directory contains `ItemsTab.vue`, which is rendered inside the `/admin` dashboard's Items tab instead.

The route is registered at `routes/web.php:77`. Item management is fully reachable through `/admin`, so nothing is functionally lost, but the standalone route is broken for anyone who lands on it.

**Fix:** either delete the `GET /admin/items` route, or point it at a real page component. The `POST`/`PUT`/`DELETE` sibling routes on the same path are in use and must stay.

Screenshot: `storage/screenshots/admin-items-broken.png`

---

## Medium

### 2. Footer owner link is an uninterpolated template string

`resources/js/components/custom/Footer.vue:10`:

```vue
<a href="{{ linkDict.OWNER.path }}">
```

Missing the `v-bind` colon, so the literal string `{{ linkDict.OWNER.path }}` is emitted as the `href`. The link text on line 11 interpolates correctly because it is in a text node, which masks the bug visually. The intended target exists: `useLinkDictionary.ts:34` defines `OWNER: makeLink('Siat-s', 'https://siat-s.deviantart.com')`.

Appears on every page of the site.

**Fix:** `:href="linkDict.OWNER.path"`

### 3. Unknown URLs return HTTP 200

`https://rattlesnake-mountain.test/this-page-does-not-exist` returns status 200 with the page title still reading "Home - Rattlesnake Mountain". The body correctly shows "Page missing or still under construction".

The `Route::fallback` at `routes/web.php:300` is not what handles this. The CMS catch-all at `routes/web.php:296` (`/{slug}` where `[a-zA-Z0-9\-_]+`) matches first and `StaticPageController@show` renders the not-found view with a 200.

Crawlers will index every mistyped URL as a live page.

**Fix:** have the CMS controller `abort(404)` on an unresolved slug, and set a 404 status on the `NotFound` Inertia render. Give the page its own title.

Screenshot: `storage/screenshots/404-page.png`

### 4. Public inventory dumps the full item catalog

`/u/{user}/inventory` renders every item in the catalog (~60 rows) at quantity 0 and 0% progress for a user who owns nothing. The private `/inventory` view for the same user shows a terse "No items in your inventory yet."

If this is deliberate collection-progress tracking, it still reads as unfinished next to the private view, and it is a heavy table for a new player.

### 5. `/stats-leveling` has missing content

The page promises "a reminder on what each stat category does", then lists `Strength:`, `Dexterity:`, `Constitution:`, `Intelligence:`, `Wisdom:`, `Charisma:`, `Experience:`, `Health:` with nothing after each colon.

Unlike the visually similar lists on `/character-upload` and `/breeding-foaling`, this one is not a copy/paste form template. It is genuinely empty CMS content.

Screenshot: `storage/screenshots/stats-leveling.png`

### 6. Cookie consent banner overlaps content

The fixed banner sits over page content until dismissed. On desktop it obscures the top of the body copy. On mobile at 390x844 it fully covers the "Create account" submit button on `/register`, so a new user cannot sign up without dismissing it first.

**Fix:** reserve layout space for the banner, or raise the page's bottom padding while it is visible.

---

## Low

### 7. Extraneous class warning on every CMS page

```
[Vue warn]: Extraneous non-props attributes (class) were passed to component but could not be automatically inherited because component renders fragment or text or teleport root nodes.
```

Emitted from `<Layout title="..." class="info-page">` in `DynamicInfo.vue`. The `info-page` class never reaches the DOM, making it a dead styling hook. Either give `Layout` a single root element and let it inherit, or bind the class explicitly inside the layout.

### 8. Duplicate `<h1>` on CMS pages

Most CMS-rendered pages emit two `<h1>` elements, e.g. the "last updated by" line plus a mid-body callout rendered as `<h1><strong>`. Looks like the markdown-to-HTML mapping promotes bold-emphasis blocks to H1. Breaks heading hierarchy for screen readers and for search.

### 9. Raw numeric ID inputs where a picker belongs

- `/herds/create` — "Herd Leader (Optional)" is a bare number spinbutton expecting a horse ID.
- `/breedings` — "Recipient user ID" on the slot-transfer offer is a bare number spinbutton.

A player has no way to discover another record's numeric ID. `/horses/create` already uses a searchable select for its herd reference, so the pattern to copy exists in the codebase.

### 10. Mobile hamburger toggle has no accessible name

At 390x844 the nav toggle appears in the accessibility tree as a button with an empty label, no visible text and no `aria-label`. Screen reader users get an unlabeled control that gates the entire navigation.

**Fix:** `aria-label="Menu"` or visually hidden text.

### 11. Section-switch pills on `/rules` are small and low contrast at mobile width

The "general rules" / "art and literature" switcher renders small against its border at 390px, with a borderline tap-target size.

Screenshot: `storage/screenshots/mobile-rules.png`

### 12. "Website by Abature Studio" links to `#`

Footer placeholder link.

---

## Not bugs, noted for the record

- **Broken horse images across `/horses`, `/horses/{id}`, `/herds/{id}`, `/u/{user}/horses`.** Every one is a `via.placeholder.com` URL failing with `net::ERR_CONNECTION_CLOSED`; the service is dead. This is seed data, not production data: `database/factories/HorseFactory.php:26` uses `fake()->optional(0.7)->imageUrl(400, 400, 'horses')`. Worth swapping for a local placeholder asset so that future visual audits are not full of broken image icons.
- **`/users` requires login.** It sits inside the auth middleware group at `routes/web.php:188` and correctly redirects a guest to `/login`. Flagging only to confirm the user directory is meant to be private.
- **`/contact-us` mailto points at `abaturestudio@gmail.com`**, the dev studio rather than a game-owner address. Confirm this is intentional.
- **Guest protection is correct.** `/dashboard`, `/admin`, `/inventory`, `/trades` all redirect to `/login`. `/register` and `/forgot-password` correctly bounce an authenticated user to `/dashboard`.

## Clean

No console errors, no failed requests, no visible layout or content problems beyond the site-wide items above.

**Public and static:** `/`, `/login`, `/register`, `/forgot-password`, `/lore`, `/getting-started`, `/privacy-policy`, `/contact-us`, `/lifespans`, `/wildlife`, `/herd-unity`, `/story-progression`, `/player-vs-player`, `/claiming-npcs`, `/character-handbook`, `/character-upload`, `/breeding-foaling`

**Game:** `/dashboard`, `/horses/create`, `/herds`, `/herds/create`, `/breedings`, `/shop`, `/inventory`, `/trades`, `/inbox`, `/leaderboard`, `/users`, `/settings/profile`, `/settings/password`, `/settings/appearance`, `/u/{user}`

**Admin:** `/admin` — Submissions, Rollers, Users, Items, Shop, Lifecycle and CMS tabs all render without errors. No write-shaped control was clicked.

**Mobile (390x844):** `/`, `/login`, `/register`, `/leaderboard` — no horizontal overflow, no cut-off text, hamburger nav opens and lists every link legibly.

---

## Tooling note

The Playwright MCP server saved several screenshots to the repo root rather than the configured `storage/screenshots` output directory, despite relative filenames being passed. The strays were moved manually. Worth confirming `--output-dir` is honored if it recurs.
