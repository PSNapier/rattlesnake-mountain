# Roadmap Done

## [025] Playwright MCP for Agent-Driven Browser Verification

**Status:** `done`
**Depends On:** none

### Goal

Coding agents working in this repo can drive the real app in a browser to verify a change rendered correctly, instead of relying on Pest feature assertions that never exercise Inertia or the compiled front end. This is tooling for manual verification, not a committed test suite.

### Scope

- Playwright MCP server registered in a committed project `.mcp.json`
- Convention documented in `CLAUDE.md`: base URL, headless default, screenshot destination, login procedure
- `PLAYWRIGHT_TEST_EMAIL` / `PLAYWRIGHT_TEST_PASSWORD` added to `.env` and `.env.example` (example values only in the committed file)
- `storage/screenshots` created and gitignored
- NOT in scope: a committed Playwright test suite, `@playwright/test` as a project dependency, CI wiring, visual regression baselines, seeded or isolated test databases, a local impersonation login route

### Technical Notes

- Decisions came from a grill session on 2026-08-28. Explicitly rejected: a committed e2e suite, CI execution, screenshot diffing, per-test fixtures, and a `storageState` session file. This item is a config change plus documented conventions, deliberately.
- Runs against the live Herd site using the working dev MySQL database (`rattlesnake_mountain`). No isolation, no rollback. Accepted risk: an agent clicking through admin, lifecycle, or breeding flows mutates real dev data.
- Base URL is `https://rattlesnake-mountain.test`, not the `http://` value of `APP_URL`: Herd 301s plain HTTP to HTTPS with a self-signed certificate, so `.mcp.json` passes `--ignore-https-errors`.
- The agent logs in through the real login form each session. No session file is persisted (`--isolated`).
- Local `.env` holds a real dev account's credentials; `.env.example` carries placeholders only. `.env` stays gitignored.
- `storage/screenshots` is kept in the repo with a self-ignoring `.gitignore` (`*` plus `!.gitignore`), the same pattern Laravel uses elsewhere, so a fresh clone has the directory but never tracks its contents.
- This repo had no `CLAUDE.md` before this item. One was created for the conventions.
- `DEV_PASSWORD` is a staging-only gate to keep staging off the public web. It is not enabled locally and plays no part in this setup.
- Item [020] (local vs CI test discrepancies) stays unaffected: nothing here runs in CI.

### Acceptance Criteria

- [x] `.mcp.json` registers the Playwright MCP server and is committed
- [x] A fresh agent session in this repo has browser tools available without extra setup — verified by launching the server with the exact `.mcp.json` args and running an MCP handshake: it reports `Playwright 1.63.0-alpha-2026-08-05` and lists the `browser_*` tools
- [x] Agent can log in and reach an authenticated page reading credentials from `.env`, with no credentials pasted into the prompt
- [x] Screenshots land in `storage/screenshots` and that path is gitignored
- [x] Browser runs headless by default
- [x] `CLAUDE.md` documents the base URL, the login sequence, and the screenshot path
- [x] `.env.example` lists the two new variables with placeholder values

### Tests

- [x] No automated tests. This is agent tooling with no application code. Verified out of session with a throwaway headless script driving the same flow the MCP server will: navigated `https://rattlesnake-mountain.test/login`, filled the two `PLAYWRIGHT_TEST_*` values read from `.env`, landed on `/dashboard`, wrote a screenshot into `storage/screenshots`, and confirmed `git status` stayed clean.

---

## [012] Light Mode Only and Legibility Pass

**Status:** `done`
**Depends On:** none

### Goal

The site is light-mode only (no dark theme / appearance variants), with a per-page pass for contrast/legibility, plus landing cleanup of obsolete ToyHouse / admin-account CTAs where still present.

### Scope

- Remove or disable dark-mode theme switching; force light appearance
- Strip or neutralize problematic `dark:` usage that assumes a dual theme (prefer light-readable defaults)
- Per-page legibility check (text/background contrast)
- Landing: remove or replace obsolete links (ref doc: ToyHouse + rattlesnake-admin; Home still promotes `@rattlesnakeadmin`)
- NOT in scope: full visual redesign / brand refresh; WYSIWYG CMS

### Technical Notes

- Full write-up in [`reference/LIGHT-MODE-AUDIT.md`](reference/LIGHT-MODE-AUDIT.md): what was removed, the contrast measurements, and the per-page list
- The `dark:` classes were **not** inert. `app.css` had the custom dark variant commented out, which reads as "already off", but Tailwind v4 then falls back to its built-in variant compiling to `@media (prefers-color-scheme: dark)`. Every one was live for visitors whose OS was set to dark
- Two `@source` globs fed non-app CSS into the build: compiled views under `storage/` (Laravel's exception-renderer, with its own theme switcher) and the vendor paginator. The storage glob is gone; the paginator views were published to `resources/views/vendor/pagination/`, stripped, and the glob repointed there
- Removed: `initializeTheme()`, `useAppearance.ts`, `AppearanceTabs.vue`, `HandleAppearance` middleware, and the `appearance` cookie exemption. The built stylesheet now has zero `dark:` utilities and zero `prefers-color-scheme` queries
- Contrast fixes: `h2`/`h3` `shakespeare-400` → `700` (was 1.79:1, fails at any size), `h4`/`h5` `new-orleans-500` → `800` (was 1.99:1), body `cape-palliser-600` → `700`, and markdown links in `.box` get colour plus an underline
- Deleted `resources/js/pages/Home.vue`, an unrouted duplicate of `Welcome.vue` that still carried the obsolete `@rattlesnakeadmin` CTA. Deep links to that DeviantArt account in `useLinkDictionary` are resource links (lineart, map, design guide), not the promotional CTA, so they stay
- The settings tab is relabelled **Avatar** since the theme switcher is gone. The route stays `/settings/appearance` and keeps its route names, so existing links and bookmarks still work
- Accepted: white on `shakespeare-500` buttons measures 3.64:1, below AA for the `text-sm` labels used. Fixing it means changing the primary brand surface site-wide, which is a client decision, not a bug fix
- Accepted: contrast was computed from palette tokens, not sampled from screenshots, so stacked translucent surfaces (dialog overlays, `/50` hover states) are not covered
- Pre-existing, untouched: 18 files under `resources/` fail `prettier --check`, and `eslint` reports 2 errors, both predating this work

### Acceptance Criteria

- [x] No user-facing dark/theme toggle; app renders consistently in light mode
- [x] Documented list of pages checked for contrast issues; critical failures fixed
- [x] Obsolete ToyHouse / rattlesnake-admin promotional links removed or replaced per product decision
- [x] Smoke test: key public + auth pages render without theme flash to dark

### Tests

- [x] `tests/Feature/LightModeTest.php::it_renders_key_pages_without_a_theme_toggle`
- [x] `tests/Feature/LightModeTest.php::it_omits_obsolete_toyhouse_and_admin_account_links_from_home`

---

## [006] Player Trading

**Status:** `done`
**Depends On:** none

### Goal

Players can offer and accept simple item transfers using the existing `user_items` inventory, replacing Discord `#trading-post` for basic trades.

### Scope

- Offer create / accept / decline / cancel between two users
- Atomic quantity transfer with max_count enforcement
- Basic trade history for both parties
- NOT in scope: horse trading, auction house, Scorpion-only shop (already exists), escrow disputes UI beyond cancel/decline

### Technical Notes

- `Trade` + `TradeItem` over the existing `user_items` pivot. Offers are one-way: the sender gives, the recipient accepts. No escrow, so nothing leaves the sender's inventory until accept
- [`TradeService`](app/Services/TradeService.php) holds the transfer. Every inventory row the trade touches is created at zero and locked in one globally consistent `(item_id, user_id)` order before any arithmetic. Per-trade sender-then-recipient ordering deadlocks the moment two players trade the same item in opposite directions
- Rows are created before they are locked on purpose: locking a row that does not exist takes only a gap lock, and gap locks disappear under `READ COMMITTED`, which would let two first-time credits to the same player overwrite each other
- Accept re-checks holdings, `is_active`, and recipient `max_count`, because an offer can sit open while the world changes underneath it
- Reachable from the user menu ("My Trades"). The recipient field is a name search fed by a `recipients` prop, since players never see each other's numeric ids
- Accepted: the deadlock ordering and the `READ COMMITTED` insert race are argued from the lock protocol, not covered by a test. Pest cannot deterministically interleave two transactions here
- Accepted: the `recipients` list is unbounded and loads every non-banned player on each page view, matching the existing `/users` route. Worth revisiting as a search endpoint when the player count grows
- Accepted: trade history is offset-paginated with no pruning, so deep pages get slower for heavy traders

### Acceptance Criteria

- [x] User A can offer items to User B; B can accept or decline
- [x] Accept moves quantities atomically; inventories never go negative
- [x] Cancel works for open offers; accepted trades immutable
- [x] Pest tests cover happy path, insufficient qty, and unauthorized accept

### Tests

- [x] `tests/Feature/TradeTest.php::it_creates_an_offer_between_two_users`
- [x] `tests/Feature/TradeTest.php::it_transfers_quantities_atomically_on_accept`
- [x] `tests/Feature/TradeTest.php::it_rejects_an_offer_exceeding_owned_quantity`
- [x] `tests/Feature/TradeTest.php::it_forbids_a_third_party_from_accepting`
- [x] `tests/Feature/TradeTest.php::it_cancels_open_offers_and_freezes_accepted_trades`
- [x] `tests/Feature/TradeTest.php::it_rejects_an_accept_that_would_exceed_the_recipient_max_count`
- [x] `tests/Feature/TradeTest.php::it_refuses_to_accept_an_item_retired_after_the_offer`
- [x] `tests/Feature/TradeTest.php::it_refuses_to_accept_a_trade_whose_items_were_deleted`
- [x] `tests/Feature/TradeTest.php::it_forbids_a_banned_user_from_acting_on_an_open_trade`
- [x] `tests/Feature/TradeTest.php::it_counts_rejected_offers_against_the_rate_limit`
- [x] `tests/Feature/TradeTest.php::it_credits_a_recipient_who_has_never_held_the_item`
- [x] `tests/Feature/TradeTest.php::it_offers_to_a_recipient_picked_by_name`

---

## [013] Coming Soon Placeholders for Deferred Gameplay

**Status:** `done`
**Depends On:** none

### Goal

Activities/story progression and seasonal events clearly show Coming Soon in-app so players are not sent into incomplete Discord-only flows as if they were finished product features.

### Scope

- Coming Soon treatment on story-progression / activities entry points and seasonal/wildlife event UX as agreed
- Keep lore readable where it is documentation; distinguish “rules docs” vs “playable feature”
- NOT in scope: building the systems ([015], [016]); removing CMS lore content

### Technical Notes

- Implemented as a `coming_soon` boolean on `cms_pages`, rendered as a banner above the content in [`DynamicInfo.vue`](resources/js/components/custom/DynamicInfo.vue). No CMS content was deleted
- Flagged: `story-progression`, `player-vs-player`, `claiming-npcs` (claiming is gated on story-progression rolls), `wildlife` (seasonal hub). Not flagged: `herd-unity` and `lifespans` describe mechanics rather than offering a play action, and `breeding-foaling` shipped in-app with [005]
- Staff can clear the banner from the admin CMS tab when a feature ships, so no deploy is needed. `CmsPageSeeder` holds the list for fresh installs; the migration carries a frozen copy for existing databases
- Accepted: the Pest tests assert the server sends `coming_soon`, not that the banner paints. There is no component-test harness in the repo, so a regression that dropped the `v-if` in `DynamicInfo.vue` would not be caught
- Accepted: the migration backfill matches on slug and silently updates zero rows if an environment renamed a page before migrating. Recoverable from the admin CMS tab

### Acceptance Criteria

- [x] Primary play entry points for activities and seasonal events show Coming Soon
- [x] No dead “submit play” CTA that posts nowhere
- [x] Lightweight test or snapshot asserting Coming Soon presence on those routes

### Tests

- [x] `tests/Feature/ComingSoonTest.php::it_shows_coming_soon_on_activity_entry_points`
- [x] `tests/Feature/ComingSoonTest.php::it_shows_coming_soon_on_seasonal_event_entry_points`
- [x] `tests/Feature/ComingSoonTest.php::it_exposes_no_submit_play_cta_on_those_routes`
- [x] `tests/Feature/ComingSoonTest.php::it_leaves_reference_pages_without_a_coming_soon_banner`
- [x] `tests/Feature/ComingSoonTest.php::it_lets_staff_clear_a_coming_soon_banner_once_the_feature_ships`

---

## [007] Announcements System

**Status:** `done`
**Depends On:** none

### Goal

Staff can post announcements that appear on the Home page, replacing the hardcoded September 2023 news box.

### Scope

- `Announcement` model (title, body, published_at, author)
- Admin CRUD tab or section
- Home displays latest published announcement(s)
- Remove stale hardcoded news copy from [`Welcome.vue`](resources/js/pages/Welcome.vue)
- NOT in scope: CMS rich-text/WYSIWYG ([019]); making entire Home CMS-editable

### Technical Notes

- Home is not a `CmsPage` — props come from the `/` route closure via `Announcement::publicFeed()`
- Announcement body is plain text rendered with `whitespace-pre-line`; rich text is deferred to [019]
- Admin CRUD lives as a section under the existing CMS tab and is gated by the `admin.cms` capability, so no new capability area or role-matrix migration was needed
- `published_at` is normalised to the app timezone by a model mutator. Eloquent's plain `datetime` cast preserves the incoming offset, which would let a non-UTC admin publish hours early or late
- Admin list is capped at the 50 most recent announcements with no pagination. Accepted: the public feed is bounded at 3 and the admin list only degrades past 50 rows

### Acceptance Criteria

- [x] Admin can create/update/unpublish announcements
- [x] Home shows current published announcement(s); no 2023 hardcoded activity-check copy
- [x] Guests can read announcements; only staff can manage
- [x] Pest tests for public display and admin authz

### Tests

- [x] `tests/Feature/AnnouncementTest.php::it_shows_the_latest_published_announcement_on_home`
- [x] `tests/Feature/AnnouncementTest.php::it_hides_unpublished_announcements_from_guests`
- [x] `tests/Feature/Admin/AdminAnnouncementTest.php::it_lets_staff_create_and_unpublish_announcements`
- [x] `tests/Feature/Admin/AdminAnnouncementTest.php::it_forbids_non_staff_from_managing_announcements`
- [x] `tests/Feature/Admin/AdminAnnouncementTest.php::it_stores_an_offset_aware_publish_time_as_the_correct_instant`

---

## [005] Breeding System (Punnett-Square)

**Status:** `done`
**Priority:** high
**Depends On:** [004]

### Goal

Players submit breeding requests; staff with rollers access publish two Punnett genotype options; players choose one and create a pending foal. Breeding authorization uses transferable per-horse slots (10), not horse ownership alone.

### Scope

-   Strict local Punnett provider behind a genetics-provider contract (external API seam reserved)
-   Transferable breeding slots with offer/accept and Sanctuary staff grants
-   Create pending foal after player chooses one of two immutable results; update `bloodline` / `progeny` / `bred_by`
-   Add nullable horse `sex`; staff backfill required for existing horses
-   NOT in scope: phenotype reader; Stones; seasons/estrus/checkpoints/attempt limits; twins/rerolls; health/stats/traits rolls; full foaling story UI

### Technical Notes

-   Provider contract + local Punnett: `app/Services/Contracts/BreedingGeneticsProvider.php`, `config/breeding.php`
-   Admin UI: Rollers Breeding section; player UI: `/breedings`
-   Deferred: [023] advanced breeding lifecycle + Stones; [024] shared phenotype reader

### Acceptance Criteria

-   [x] Breeding two horses with known genos yields offspring geno consistent with Punnett rules (unit-tested)
-   [x] Bloodline/progeny relationships update correctly
-   [x] Ineligible pairs rejected with clear errors (same horse, wrong/missing sex, underage, dead, bad geno, missing slots)
-   [x] Feature test covers create-foal happy path
-   [x] Documented inheritance edge cases listed if deferred

---

## [004] Lifecycle Automation

**Status:** `done`
**Priority:** high
**Depends On:** none

### Goal

Horses auto-age on the schedule stored in `lifecycle_settings`, and old NPC death rolls produce **proposals** that admins confirm before horses are marked dead. Health roll *application* deferred to [022].

### Scope

-   Artisan command + scheduler entry reading `LifecycleSetting`
-   Age horses by configured game-years each run (years + months via `age_months`)
-   NPC death-roll proposals queued for admin confirmation in Lifecycle tab (not auto-delete)
-   Logging of aging/death outcomes for support
-   Health roll settings retained in UI but not applied (deferred → [022])
-   NOT in scope: auto-freeze ([018]); player PvP death; fully automatic NPC deletion without review; applying health ± from injury

### Technical Notes

-   Model: [`app/Models/LifecycleSetting.php`](app/Models/LifecycleSetting.php)
-   Service: [`app/Services/LifecycleAgingService.php`](app/Services/LifecycleAgingService.php)
-   UI: [`resources/js/pages/admin/LifecycleTab.vue`](resources/js/pages/admin/LifecycleTab.vue)
-   Command: `horses:lifecycle` scheduled daily in [`routes/console.php`](routes/console.php)
-   NPC = `is_npc` (auto for Sanctuary-owned; cleared on claim); soft death via `died_at`

### Acceptance Criteria

-   [x] Scheduled command ages eligible horses per settings and advances next-update date
-   [x] Health roll settings persist; application deferred to [022] (injury/HP + story progression)
-   [x] NPC death proposals appear for admin confirm/reject; confirm applies death state
-   [x] Pest tests cover aging math, proposal creation, and confirm path
-   [x] Dry-run or admin “run now” supported for staging

---

## [021] Admin-Managed User Role Matrix

**Status:** `done`
**Priority:** high
**Depends On:** none

### Goal

Admins can view and edit the role → capability matrix (which admin areas each role can access) from the admin panel, instead of capabilities being hardcoded in the `Role` enum.

### Scope

-   Admin UI (Users tab or new section) showing roles × capability areas as an editable matrix
-   Persist matrix in DB (e.g. `role_capabilities` table or config-backed settings model); `Role::capabilities()` reads from it with sensible defaults
-   Only Admin role can edit the matrix; Admin's own `users` capability cannot be removed (no lockout)
-   Seeder/migration establishing current defaults from existing enum matrix
-   NOT in scope: creating/deleting roles (enum stays fixed); per-user capability overrides; full permissions package (spatie) unless approved

### Technical Notes

-   Current matrix hardcoded: [`app/Models/Role.php`](app/Models/Role.php) `capabilities()`
-   Capability checks: `User::hasCapability()` used by admin middleware/controllers ([`app/Http/Controllers/Admin/DashboardController.php`](app/Http/Controllers/Admin/DashboardController.php))
-   Role assignment UI already exists in Admin Users tab (`UpdateUserRoleRequest`); this manages what roles *can do*, not who has them
-   Cache matrix lookups; invalidate on save
-   Security: guard against self-lockout and privilege escalation by non-admin staff

### Acceptance Criteria

-   [x] Admin can toggle capability areas per role in admin UI; changes persist
-   [x] Capability checks throughout app respect the stored matrix
-   [x] Non-admin staff cannot access or edit the matrix
-   [x] Admin role cannot lose `users` capability (lockout guard tested)
-   [x] Migration/seeder installs defaults matching current enum behavior
-   [x] Pest tests cover toggle persistence, enforcement, and authorization

---

## [003] Recruit-a-Friend Rewards

**Status:** `done`
**Priority:** high
**Depends On:** [001]

### Goal

When a new player registers with a valid referrer, both recruiter and recruit receive the documented referral bonuses on top of the welcome package.

### Scope

-   Reward both parties using existing `referred_by_username` field
-   Validate referrer exists and is eligible (not self-referral)
-   **Spec-flagged:** confirm exact bonus amounts with client (CMS documents +100 Scorpions, stones/herbs/feathers choices, and submission bonuses for 3 months — submission bonuses may need placeholder until activities exist)
-   NOT in scope: ongoing +10 Scorpions per submission until activities system ([015])

### Technical Notes

-   Referral link: `referrals` table (`recruit_id`, `referrer_id`, `granted_at`, `revoked_at`); registration stores `referrer_id` via searchable picker; `users.referred_by_username` kept for display
-   Grant trigger: `ReferralRewardService` on `Verified` (`GrantReferralRewards` listener); `skip_email_verification` fires `Verified` after `markEmailAsVerified()`
-   Config: [`config/referral-rewards.php`](config/referral-rewards.php) (`each` bundle); voucher pools in [`config/vouchers.php`](config/vouchers.php)
-   Registration search: `GET /register/referrer-search` (guest, throttled, min 2 chars, limit 10); [`Register.vue`](resources/js/pages/auth/Register.vue)
-   Voucher redeem: generalized in `WelcomePackageService::redeemVoucher` + [`Inventory/Index.vue`](resources/js/pages/Inventory/Index.vue)
-   Tests: [`tests/Feature/ReferralRewardTest.php`](tests/Feature/ReferralRewardTest.php), [`ReferrerSearchTest.php`](tests/Feature/ReferrerSearchTest.php), [`RedeemVoucherTest.php`](tests/Feature/RedeemVoucherTest.php)
-   **Pending client sign-off:** voucher pool contents (stones/herbs/feathers). **Deferred to [015]:** +10 Scorpions / +1 stat per submission for 3 months (`referrals.granted_at` is window start)

### Acceptance Criteria

-   [x] Valid referral grants configured bonuses to both users
-   [x] Invalid / self / missing username does not grant bonuses (and does not block registration if field optional)
-   [x] Pest tests cover happy path and abuse cases (self-referral)
-   [x] Open amount questions documented in Technical Notes if still pending at build time

---

## [002] Staff Role Gates

**Status:** `done`
**Priority:** high
**Depends On:** none

### Goal

`Designer`, `StoryAdmin`, and `GameMaster` roles unlock the admin features they are meant to use, instead of being cosmetic labels while only `Admin` passes `access-admin`.

### Scope

-   Define per-role capability map (e.g. Designer → submissions review; StoryAdmin/GM → rollers/lifecycle; Admin → full)
-   Replace or extend `Gate::define('access-admin')` and tab-level authorization
-   Update admin UI to hide tabs the role cannot use
-   NOT in scope: inventing a Founder role; changing Discord staff process

### Technical Notes

-   Capability map: `Role::capabilities()` in [`app/Models/Role.php`](app/Models/Role.php); `User::isStaff()` / `hasCapability()`
-   Gates: `access-admin` = any staff; per-area `admin.*` in [`app/Providers/AppServiceProvider.php`](app/Providers/AppServiceProvider.php)
-   Routes: per-area `can:admin.*` middleware in [`routes/web.php`](routes/web.php)
-   Admin shell: [`resources/js/pages/admin/Index.vue`](resources/js/pages/admin/Index.vue) — tabs + props scoped via `adminCapabilities`
-   Login sync: [`app/Listeners/SyncAdminRoleFromEmailList.php`](app/Listeners/SyncAdminRoleFromEmailList.php) only toggles Admin ↔ User; preserves Designer/StoryAdmin/GameMaster
-   Tests: [`tests/Feature/StaffRoleGatesTest.php`](tests/Feature/StaffRoleGatesTest.php) — dataset-driven role × area matrix
-   Matrix (pending client confirm): Designer → submissions; StoryAdmin/GameMaster → rollers + lifecycle; Admin → all

### Acceptance Criteria

-   [x] Each non-admin staff role can access at least one documented admin capability
-   [x] Users with `Role::User` remain forbidden from all admin routes
-   [x] Pest tests cover allow/deny per role for representative routes
-   [x] Admin nav only shows authorized tabs

---

## [001] Welcome Package on Registration

**Status:** `done`
**Priority:** high
**Depends On:** none

### Goal

New players receive the documented welcome package in their inventory immediately on account creation so they can start character customization without Discord handouts.

### Scope

-   Grant on successful registration: 500 Scorpions, 1 White-Modifier Stone, 1 Cream or Pearl Stone (player choice or deferred claim), 2 Randomized Stones, 2 Random Herbs, 2 Random Feathers
-   Ensure required `Item` catalog rows exist (extend `ItemSeeder` / shop catalog as needed)
-   Idempotent grant (no double-grant on re-verify / re-register edge cases)
-   NOT in scope: recruit-a-friend bonus package ([003]), item consumption UI ([011])

### Technical Notes

-   Spec: [`.cursor/burndown.md`](.cursor/burndown.md), CMS Getting Started (`CmsPageSeeder`)
-   Grant: `WelcomePackageService` + `GrantWelcomePackage` listener on `Registered`; config in `config/welcome-package.php`
-   Idempotency: `users.welcome_package_granted_at`; repair via `welcome-package:grant {user}`
-   Cream/Pearl: deferred via `Cream/Pearl Stone Voucher` + inventory redeem route
-   Catalog: [`database/seeders/ItemSeeder.php`](database/seeders/ItemSeeder.php) extended with stones/voucher/feathers; herbs from `ShopCatalogSeeder`
-   **Audits:** [reference/FEATURE-AUDIT.md](reference/FEATURE-AUDIT.md), [reference/DATA-LAYER-AUDIT.md](reference/DATA-LAYER-AUDIT.md)

### Acceptance Criteria

-   [x] New registration grants the full welcome package quantities to `user_items`
-   [x] Missing catalog items are seeded or creation fails loudly in tests
-   [x] Pest feature test covers grant on register; no duplicate grant on email verify
-   [x] Grant is logged or otherwise auditable for support

---

## [001] Example Completed Task

**Status:** `done`
**Depends On:** none

### Goal

One-paragraph description of what this task accomplished and why.

### Scope

- What was included
- What was explicitly out of scope

### Technical Notes

- Key implementation decisions and relevant files/tables/commands

### Acceptance Criteria

- [x] Criterion one
- [x] Criterion two
- [x] Criterion three

---
