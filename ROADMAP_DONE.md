# Roadmap Done

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
