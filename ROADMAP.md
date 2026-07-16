# Roadmap

<!-- Next task number: [022] -->

## [003] Recruit-a-Friend Rewards

**Status:** `next`
**Priority:** high
**Depends On:** [001]

### Goal

When a new player registers with a valid `referred_by_username`, both recruiter and recruit receive the documented referral bonuses on top of the welcome package.

### Scope

-   Reward both parties using existing `referred_by_username` field
-   Validate referrer exists and is eligible (not self-referral)
-   **Spec-flagged:** confirm exact bonus amounts with client (CMS documents +100 Scorpions, stones/herbs/feathers choices, and submission bonuses for 3 months — submission bonuses may need placeholder until activities exist)
-   NOT in scope: ongoing +10 Scorpions per submission until activities system ([015])

### Technical Notes

-   Column: `users.referred_by_username` ([migration](database/migrations/2026_01_26_134807_add_referred_by_username_to_users_table.php))
-   CMS copy in Getting Started recruit-a-friend section
-   Prefer config-driven reward table so amounts can change without code edits

### Acceptance Criteria

-   [ ] Valid referral grants configured bonuses to both users
-   [ ] Invalid / self / missing username does not grant bonuses (and does not block registration if field optional)
-   [ ] Pest tests cover happy path and abuse cases (self-referral)
-   [ ] Open amount questions documented in Technical Notes if still pending at build time

---

## [004] Lifecycle Automation

**Status:** `next`
**Priority:** high
**Depends On:** none

### Goal

Horses auto-age on the schedule stored in `lifecycle_settings`, health rolls apply, and old NPC death rolls produce **proposals** that admins confirm before horses are marked dead.

### Scope

-   Artisan command + scheduler entry reading `LifecycleSetting`
-   Age horses by configured game-years each run; apply health roll within min/max
-   NPC death-roll proposals queued for admin confirmation in Lifecycle tab (not auto-delete)
-   Logging of aging/health/death outcomes for support
-   NOT in scope: auto-freeze ([018]); player PvP death; fully automatic NPC deletion without review

### Technical Notes

-   Model: [`app/Models/LifecycleSetting.php`](app/Models/LifecycleSetting.php)
-   UI: [`resources/js/pages/admin/LifecycleTab.vue`](resources/js/pages/admin/LifecycleTab.vue) (NPC deaths currently placeholder)
-   Scheduler today: only `model:prune` in [`routes/console.php`](routes/console.php)
-   Define “NPC horse” clearly (e.g. Sanctuary-owned / unclaimed flag) before coding

### Acceptance Criteria

-   [ ] Scheduled command ages eligible horses per settings and advances next-update date
-   [ ] Health rolls update horse health within configured bounds
-   [ ] NPC death proposals appear for admin confirm/reject; confirm applies death state
-   [ ] Pest tests cover aging math, proposal creation, and confirm path
-   [ ] Dry-run or admin “run now” supported for staging

---

## [005] Breeding System (Punnett-Square)

**Status:** `next`
**Priority:** high
**Depends On:** none

### Goal

Players (or staff) can breed two eligible horses using Punnett-square genetics derived from existing randomizer geno config, producing offspring that update `bloodline` / `progeny`.

### Scope

-   Inherit loci/modifiers from parent `geno` strings using `config/horse-randomizer/genetics.php`
-   Create foal horse record (pending or draft per existing approval norms)
-   Update parent `progeny` and child `bloodline` / `bred_by`
-   **Spec-flag:** cream/pearl and rare-gene / stone requirements — confirm edge cases with client mid-build
-   NOT in scope: full foaling story UI; Discord breeding forms replacement beyond core genetics; seasonal breeding affixes

### Technical Notes

-   Reuse: [`app/Services/HorseRandomizerService.php`](app/Services/HorseRandomizerService.php), [`config/horse-randomizer/`](config/horse-randomizer/)
-   Horse fields: `geno`, `bloodline`, `progeny`, `bred_by` already on `horses`
-   Admin Rollers tab has Breeding placeholder — wire or add player-facing flow under herds/horses

### Acceptance Criteria

-   [ ] Breeding two horses with known genos yields offspring geno consistent with Punnett rules (unit-tested)
-   [ ] Bloodline/progeny relationships update correctly
-   [ ] Ineligible pairs rejected with clear errors (same horse, wrong sex if required, etc.)
-   [ ] Feature test covers create-foal happy path
-   [ ] Documented inheritance edge cases listed if deferred

---

## [006] Player Trading

**Status:** `next`
**Priority:** high
**Depends On:** none

### Goal

Players can offer and accept simple item transfers using the existing `user_items` inventory, replacing Discord `#trading-post` for basic trades.

### Scope

-   Offer create / accept / decline / cancel between two users
-   Atomic quantity transfer with max_count enforcement
-   Basic trade history for both parties
-   NOT in scope: horse trading, auction house, Scorpion-only shop (already exists), escrow disputes UI beyond cancel/decline

### Technical Notes

-   Inventory: `Item` + `user_items` pivot; [`InventoryController`](app/Http/Controllers/InventoryController.php)
-   New models likely: `Trade` / `TradeItem` (or equivalent)
-   Security: authorize ownership, prevent negative qty races, rate-limit offers

### Acceptance Criteria

-   [ ] User A can offer items to User B; B can accept or decline
-   [ ] Accept moves quantities atomically; inventories never go negative
-   [ ] Cancel works for open offers; accepted trades immutable
-   [ ] Pest tests cover happy path, insufficient qty, and unauthorized accept

---

## [007] Announcements System

**Status:** `next`
**Priority:** high
**Depends On:** none

### Goal

Staff can post announcements that appear on the Home page, replacing the hardcoded September 2023 news box.

### Scope

-   `Announcement` model (title, body, published_at, author)
-   Admin CRUD tab or section
-   Home displays latest published announcement(s)
-   Remove stale hardcoded news copy from [`Welcome.vue`](resources/js/pages/Welcome.vue)
-   NOT in scope: CMS rich-text/WYSIWYG ([019]); making entire Home CMS-editable

### Technical Notes

-   Home is not a `CmsPage` — wire props from controller/route closure for `/`
-   Keep markdown or plain text consistent with existing CMS rendering if reused

### Acceptance Criteria

-   [ ] Admin can create/update/unpublish announcements
-   [ ] Home shows current published announcement(s); no 2023 hardcoded activity-check copy
-   [ ] Guests can read announcements; only staff can manage
-   [ ] Pest tests for public display and admin authz

---

## [008] Design Upload Terms and Graveyard Option

**Status:** `next`
**Priority:** high
**Depends On:** none

### Goal

Horse design create/upload requires agreement to upload terms, and herd-leader designs capture leave-game disposition (free for others vs graveyard).

### Scope

-   Required terms checkbox on horse create/upload
-   Disposition field for herd-leader designs only (not NPC designs)
-   Persist choice on horse (or related) record for later account-deletion / leave flows
-   **Spec-flag:** exact graveyard rules and admin tooling for reclaim — confirm with client
-   NOT in scope: full account-deletion automation that reassigns designs (can stub hook)

### Technical Notes

-   Create UI: [`resources/js/pages/Horses/Create.vue`](resources/js/pages/Horses/Create.vue)
-   Upload: `HorseController::uploadImage`
-   Approval queue already exists; this adds consent + metadata only

### Acceptance Criteria

-   [ ] Create/upload rejected without terms agreement
-   [ ] Herd-leader designs require disposition choice; NPC path skips or hides it
-   [ ] Choice stored and visible to admin on submission review
-   [ ] Pest validation tests for required fields

---

## [009] New-Player Onboarding Flow

**Status:** `next`
**Priority:** high
**Depends On:** none

### Goal

Players without a herd leader are prompted on the dashboard toward character creation, randomizer/claimable path, stats guidance, and horse-lines download.

### Scope

-   Detect “no herd leader yet” (no herd or herd without leader horse)
-   Dashboard prompt with clear next steps and links
-   Dismissible or persistent until leader exists (product choice — prefer persistent until complete)
-   NOT in scope: full wizard multi-step SPA; implementing claimable roller ([010]) beyond linking

### Technical Notes

-   Dashboard currently [`Users/Index`](resources/js/pages/Users/Index.vue) via dashboard route
-   Link targets: character handbook / stats-leveling CMS pages, horses create, claimable flow when ready

### Acceptance Criteria

-   [ ] New users without herd leader see onboarding prompt on dashboard
-   [ ] Users with an established herd leader do not see the prompt
-   [ ] Links resolve to real routes/pages
-   [ ] Pest or browser-level assertion for prompt visibility conditions

---

## [010] Randomized Claimable Horses

**Status:** `next`
**Priority:** high
**Depends On:** none

### Goal

In-app roller offers unclaimed/claimable designs (bachelor stallions / herd mares) so new players can adopt a Randomized Claimable without Discord forms.

### Scope

-   Pool of claimable horses (flag or Sanctuary-owned unclaimed designs)
-   Roll N options per gender (match Getting Started: three of each gender) and claim one
-   Claim assigns ownership and sets as herd leader candidate
-   NOT in scope: “Ask a Designer” phenotype commission workflow; DeviantArt gallery sync

### Technical Notes

-   Distinct from admin `HorseRandomizerService` (NPC stats) — this picks existing designed horses
-   CMS Getting Started documents current Discord process — update copy when in-app ships
-   May need `is_claimable` (or equivalent) on `horses`

### Acceptance Criteria

-   [ ] Eligible user can request a claimable roll and receive options from the pool
-   [ ] Claiming transfers ownership exactly once; horse leaves pool
-   [ ] Empty pool / ineligible user handled with clear errors
-   [ ] Pest tests for roll + claim concurrency (no double-claim)

---

## [011] Item Usage and Equipment Workflows

**Status:** `next`
**Priority:** high
**Depends On:** none

### Goal

Players can equip/dequip items on horses (and use consumables where `uses_per_unit` applies), bridging user inventory and horse/herd equipment JSON.

### Scope

-   Equip / dequip UI on horse (and optionally herd) pages
-   Consume/use flow decrementing `user_items` or horse inventory consistently
-   Respect `uses_per_unit` and `max_count`
-   NOT in scope: full crafting; shop purchase (done); trading ([006])

### Technical Notes

-   Dual model problem: relational `user_items` vs horse/herd JSON — pick one source of truth for equipped gear (document in implementation)
-   Columns: `horses.inventory`, `horses.equipment`, `herds.*`; item `uses_per_unit` migration already exists
-   Show pages currently display counts only

### Acceptance Criteria

-   [ ] Player can move an owned item onto a horse’s equipment and back to inventory
-   [ ] Consumable use decrements quantity / uses correctly
-   [ ] Unauthorized users cannot equip on others’ horses
-   [ ] Pest tests for equip, dequip, and consume

---

## [012] Light Mode Only and Legibility Pass

**Status:** `next`
**Priority:** high
**Depends On:** none

### Goal

The site is light-mode only (no dark theme / appearance variants), with a per-page pass for contrast/legibility, plus landing cleanup of obsolete ToyHouse / admin-account CTAs where still present.

### Scope

-   Remove or disable dark-mode theme switching; force light appearance
-   Strip or neutralize problematic `dark:` usage that assumes a dual theme (prefer light-readable defaults)
-   Per-page legibility check (text/background contrast)
-   Landing: remove or replace obsolete links (ref doc: ToyHouse + rattlesnake-admin; Home still promotes `@rattlesnakeadmin`)
-   NOT in scope: full visual redesign / brand refresh; WYSIWYG CMS

### Technical Notes

-   Settings Appearance currently mixes avatar + appearance: [`resources/js/pages/settings/Appearance.vue`](resources/js/pages/settings/Appearance.vue)
-   Widespread `dark:` classes in Vue components — audit systematically
-   Home CTAs: [`resources/js/pages/Welcome.vue`](resources/js/pages/Welcome.vue)

### Acceptance Criteria

-   [ ] No user-facing dark/theme toggle; app renders consistently in light mode
-   [ ] Documented list of pages checked for contrast issues; critical failures fixed
-   [ ] Obsolete ToyHouse / rattlesnake-admin promotional links removed or replaced per product decision
-   [ ] Smoke test: key public + auth pages render without theme flash to dark

---

## [013] Coming Soon Placeholders for Deferred Gameplay

**Status:** `next`
**Priority:** high
**Depends On:** none

### Goal

Activities/story progression and seasonal events clearly show Coming Soon in-app so players are not sent into incomplete Discord-only flows as if they were finished product features.

### Scope

-   Coming Soon treatment on story-progression / activities entry points and seasonal/wildlife event UX as agreed
-   Keep lore readable where it is documentation; distinguish “rules docs” vs “playable feature”
-   NOT in scope: building the systems ([015], [016]); removing CMS lore content

### Technical Notes

-   CMS pages already exist for story-progression, wildlife, PvP — prefer banner/slot over deleting content
-   Align copy with post-MVP freezer items

### Acceptance Criteria

-   [ ] Primary play entry points for activities and seasonal events show Coming Soon
-   [ ] No dead “submit play” CTA that posts nowhere
-   [ ] Lightweight test or snapshot asserting Coming Soon presence on those routes

---

## [014] Client Spec Gathering (Activities and Seasonal)

**Status:** `next`
**Priority:** high
**Depends On:** none

### Goal

Extract roll tables and rules for activities, seasonal affixes, weather, and story logs from the client’s Sheets/Discord into `reference/` specs so post-MVP build can start without ambiguity.

### Scope

-   Produce `reference/*-SPEC.md` (or similar) covering: traveling/checkpoints, art/lit submission hooks, encounter/drop tables, weather, GM tailored responses, per-horse story log, seasonal Wildlife Report / quests / affixes
-   Call out open questions for client sign-off
-   NOT in scope: implementation of those systems

### Technical Notes

-   Existing hints: Google Sheets roller links in CMS / `useLinkDictionary`; admin horse randomizer already ported as a pattern
-   Deliverable unblocks [015] and [016]

### Acceptance Criteria

-   [ ] Spec documents committed under `reference/`
-   [ ] Each major subsystem has enough detail to implement without rediscovering Discord tribal knowledge
-   [ ] Open questions explicitly listed
-   [ ] Roadmap [015]/[016] Dependencies updated to this item when specs land

---

## [015] Activities and Competitions System

**Status:** `freezer`
**Priority:** low
**Depends On:** [014]

### Goal

In-app traveling progression, art/lit submissions, admin/GM rolls, results, relationships/drops/encounters, and per-horse story logs — replacing Discord/Sheets play loop.

### Scope

-   Full play loop per client spec from [014]
-   Site-wide weather if specified
-   NOT in scope until spec signed off

### Technical Notes

-   **Blocked:** awaiting [014] client spec
-   Ref doc Site Plan → Activities/Competitions

### Acceptance Criteria

-   [ ] Spec from [014] accepted
-   [ ] End-to-end submit → approve → roll → results → story log
-   [ ] Pest coverage for core state transitions

---

## [016] Seasonal Events In-App

**Status:** `freezer`
**Priority:** low
**Depends On:** [014]

### Goal

Move Wildlife Report, quests, and seasonal affixes from Discord-only into platform features that affect rolls/rewards.

### Scope

-   Per [014] seasonal spec
-   NOT in scope: Discord bot integration unless requested later

### Technical Notes

-   **Blocked:** awaiting [014]
-   Post-MVP per launch grill decisions

### Acceptance Criteria

-   [ ] Staff can configure a season’s affixes/quests
-   [ ] Affixes affect eligible rolls/rewards as specified
-   [ ] Tests for affix application

---

## [017] Opt-In PvP

**Status:** `freezer`
**Priority:** low
**Depends On:** [015]

### Goal

Opt-in player-vs-player gameplay after core activity systems exist (ref doc: implement once everything else is done).

### Scope

-   Opt-in flag; challenge/steal/spar rules per future spec
-   NOT in scope for MVP launch

### Technical Notes

-   CMS `player-vs-player` page is documentation only today
-   Feathers in welcome package are PvP-oriented — economy exists before PvP

### Acceptance Criteria

-   [ ] Opt-in required before PvP participation
-   [ ] Core PvP actions implemented per agreed rules
-   [ ] Tests for opt-in enforcement

---

## [018] Automatic Inactivity Freeze

**Status:** `freezer`
**Priority:** low
**Depends On:** [015]

### Goal

Freeze accounts after 4 months without art/lit submissions; frozen accounts skip aging/events/PvP until unfrozen.

### Scope

-   Scheduler using submission activity (not login proxy)
-   Enforce frozen state in aging/events/PvP
-   Keep existing admin freeze + self-unfreeze
-   NOT in scope until activities submissions exist

### Technical Notes

-   Manual freeze already: `frozen_at`, Admin Users tab, profile self-unfreeze
-   **Blocked:** trigger data requires [015]
-   Deferred deliberately at launch grill

### Acceptance Criteria

-   [ ] Accounts with no submissions for 4 months auto-freeze
-   [ ] Frozen users excluded from aging/events/PvP
-   [ ] Unfreeze restores eligibility
-   [ ] Pest tests for scheduler and enforcement

---

## [019] CMS Rich Text / WYSIWYG and Home Editability

**Status:** `freezer`
**Priority:** low
**Depends On:** none

### Goal

Replace multi-box JSON CMS editing with a simpler rich-text (or in-page WYSIWYG) experience, and make Home editable once that direction is chosen.

### Scope

-   Product decision: rich-text body vs on-page WYSIWYG
-   Home (`Welcome.vue`) becomes CMS-managed or section-editable
-   NOT in scope until decision made (burndown blocked items)

### Technical Notes

-   Admin: [`resources/js/pages/admin/CmsTab.vue`](resources/js/pages/admin/CmsTab.vue) — `contentJson` multi-box
-   Home intentionally excluded from `CmsPageSeeder` historically

### Acceptance Criteria

-   [ ] Editing direction decided and documented
-   [ ] CMS pages editable via chosen editor
-   [ ] Home content editable without deploy
-   [ ] Tests for update + public render

---

## [020] Fix Local vs CI Test Discrepancies

**Status:** `freezer`
**Priority:** low
**Depends On:** none

### Goal

Tests behave the same locally and in CI so failures are trustworthy.

### Scope

-   Identify environment-specific failures (filesystem, case sensitivity, mail, vite, DB)
-   Align phpunit/pest config, env, and path assumptions
-   NOT in scope: expanding coverage for unrelated features

### Technical Notes

-   Noted in [`.cursor/burndown.md`](.cursor/burndown.md)
-   Windows vs Linux path/case issues have bitten production before (Inertia path case)

### Acceptance Criteria

-   [ ] Documented list of previously divergent tests now passing in both environments
-   [ ] CI green on main with same suite run locally
-   [ ] No skipped/commented tests left as silent CI workarounds without tickets

---

## [021] Admin-Managed User Role Matrix

**Status:** `next`
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

-   [ ] Admin can toggle capability areas per role in admin UI; changes persist
-   [ ] Capability checks throughout app respect the stored matrix
-   [ ] Non-admin staff cannot access or edit the matrix
-   [ ] Admin role cannot lose `users` capability (lockout guard tested)
-   [ ] Migration/seeder installs defaults matching current enum behavior
-   [ ] Pest tests cover toggle persistence, enforcement, and authorization
