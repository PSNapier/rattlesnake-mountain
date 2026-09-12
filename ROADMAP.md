# Roadmap

<!-- Next task number: [040] -->

## [008] Design Upload Terms and Graveyard Option

**Status:** `next`
**Depends On:** none

### Goal

Horse design create/upload requires agreement to upload terms, and herd-leader designs capture leave-game disposition (free for others vs graveyard).

### Scope

- Required terms checkbox on horse create/upload
- Disposition field for herd-leader designs only (not NPC designs)
- Persist choice on horse (or related) record for later account-deletion / leave flows
- **Spec-flag:** exact graveyard rules and admin tooling for reclaim — confirm with client
- NOT in scope: full account-deletion automation that reassigns designs (can stub hook)

### Technical Notes

- Create UI: [`resources/js/pages/Horses/Create.vue`](resources/js/pages/Horses/Create.vue)
- Upload: `HorseController::uploadImage`
- Approval queue already exists; this adds consent + metadata only

### Acceptance Criteria

- [ ] Create/upload rejected without terms agreement
- [ ] Herd-leader designs require disposition choice; NPC path skips or hides it
- [ ] Choice stored and visible to admin on submission review
- [ ] Pest validation tests for required fields

### Tests

- [ ] `tests/Feature/HorseDesignTermsTest.php::it_rejects_creation_without_terms_agreement`
- [ ] `tests/Feature/HorseDesignTermsTest.php::it_requires_disposition_for_herd_leader_designs`
- [ ] `tests/Feature/HorseDesignTermsTest.php::it_skips_disposition_for_npc_designs`
- [ ] `tests/Feature/HorseDesignTermsTest.php::it_exposes_the_stored_choice_on_admin_review`

---

## [009] New-Player Onboarding Flow

**Status:** `freezer`
**Depends On:** none
**Spec:** none

### Goal

A logged-in player whose herd has no leader sees a dismissible banner in the app layout pointing them at Getting Started, horse creation, and herd creation, so a new account has an obvious next step instead of an empty dashboard.

### Scope

- `needsOnboarding` shared Inertia prop, true when the user's role is `user` and no herd they own has `herd_leader_id` set
- Banner in `AppLayout`, shown on every authenticated page, linking to `/getting-started`, `/horses/create`, and `/herds/create`
- Per-browser dismissal in `localStorage`, no server state
- Staff roles exempt
- NOT in scope: a multi-step wizard; the claimable roller ([010]); dashboard-specific UI; splitting the dashboard from the public profile
- NOT in scope: fixing the `herd_leader_id` validation hole (see Details)

### Technical Notes

**Blocked:** the Goal originally promised stats guidance and a horse-lines download alongside character creation and the claimable path. The banner as specified links to neither, on the grounds that `/getting-started` already carries both. Awaiting client confirmation that three links are enough. Asked 2026-09-10.

**User flows:**

- **Player, no leader:** banner at the top of every authenticated page. Three links, plus a close button that hides it for that browser.
- **Player, leader set:** no banner, on any page.
- **Staff:** no banner, regardless of herd state.

**Details:**

- The dashboard is not a distinct page. `routes/web.php:38` and `:199` both render `Users/Index`, so the dashboard and every public profile share one component. A banner in `AppLayout` sidesteps that entanglement rather than untangling it, and reaches the player wherever they land.
- The flag is shared data, added to `HandleInertiaRequests::share` (`app/Http/Middleware/HandleInertiaRequests.php:41`) beside `auth` and `unreadMessageCount`. That is a herd lookup on every authenticated request: an `exists` on `herds` where `owner_id` matches and `herd_leader_id` is not null. Keep it to that one query.
- Done state is `herd_leader_id` populated, whatever the leader horse's approval status. A design sitting in the queue counts, so a player who has done the work is not nagged while waiting on staff. A rejected design leaves them looking onboarded; accepted by decision of 2026-09-10.
- `StoreHerdRequest.php:27` validates `herd_leader_id` as `exists:horses,id` only, so a crafted request can name another player's horse as leader and clear the banner. Known and deliberately ignored here, since the UI does not offer it. Worth its own item.
- Both empty states, no herd at all and a herd with a null leader, get the same banner and the same copy. One condition, one thing to test.
- Staff exemption is `role !== Role::User` against the enum at `app/Models/Role.php:8`. Not a capability check, since no area in `Role::areas()` describes it.
- Dismissal is `localStorage`, so it survives logout on that browser. Per-browser, not truly per-session. No column, no route.
- Every link target is a real route carrying no `coming_soon` flag: `/getting-started` (`routes/web.php:218`), `/horses/create` and `/herds/create` (resource routes at `:152` and `:153`). `claiming-npcs` sits in `CmsPageSeeder::COMING_SOON_SLUGS` (`:26`), which is why [010]'s claimable path is not linked yet.

### Acceptance Criteria

- [ ] A `user` with no herd, or with a herd whose `herd_leader_id` is null, receives the onboarding flag as true
      `tests/Feature/OnboardingPromptTest.php::it_flags_a_user_with_no_herd`
      `tests/Feature/OnboardingPromptTest.php::it_flags_a_user_whose_herd_has_no_leader`
- [ ] Setting `herd_leader_id` clears the flag, whether or not that horse is approved
      `tests/Feature/OnboardingPromptTest.php::it_clears_the_flag_once_a_herd_leader_is_set`
      `tests/Feature/OnboardingPromptTest.php::it_clears_the_flag_for_an_unapproved_leader`
- [ ] Staff roles never receive the flag, even with no herd leader
      `tests/Feature/OnboardingPromptTest.php::it_never_flags_staff_roles`
- [ ] The flag is present on every authenticated page, not only the dashboard
      `tests/Feature/OnboardingPromptTest.php::it_shares_the_flag_across_authenticated_routes`
- [ ] Every banner link resolves to a routable, non-coming-soon page
      `tests/Feature/OnboardingPromptTest.php::it_links_only_to_resolvable_routes`
- [ ] Banner renders for a fresh player and the close button hides it for that browser, confirmed by hand

---

## [010] Randomized Claimable Horses

**Status:** `next`
**Depends On:** none

### Goal

In-app roller offers unclaimed/claimable designs (bachelor stallions / herd mares) so new players can adopt a Randomized Claimable without Discord forms.

### Scope

- Pool of claimable horses (flag or Sanctuary-owned unclaimed designs)
- Roll N options per gender (match Getting Started: three of each gender) and claim one
- Claim assigns ownership and sets as herd leader candidate
- NOT in scope: “Ask a Designer” phenotype commission workflow; DeviantArt gallery sync

### Technical Notes

- Distinct from admin `HorseRandomizerService` (NPC stats) — this picks existing designed horses
- CMS Getting Started documents current Discord process — update copy when in-app ships
- May need `is_claimable` (or equivalent) on `horses`

### Acceptance Criteria

- [ ] Eligible user can request a claimable roll and receive options from the pool
- [ ] Claiming transfers ownership exactly once; horse leaves pool
- [ ] Empty pool / ineligible user handled with clear errors
- [ ] Pest tests for roll + claim concurrency (no double-claim)

### Tests

- [ ] `tests/Feature/ClaimableHorseTest.php::it_rolls_options_from_the_claimable_pool_per_gender`
- [ ] `tests/Feature/ClaimableHorseTest.php::it_transfers_ownership_and_removes_the_horse_from_the_pool`
- [ ] `tests/Feature/ClaimableHorseTest.php::it_prevents_double_claiming_the_same_horse`
- [ ] `tests/Feature/ClaimableHorseTest.php::it_errors_clearly_on_empty_pool_or_ineligible_user`

---

## [014] Client Spec Gathering (Activities and Seasonal)

**Status:** `next`
**Depends On:** none

### Goal

Extract roll tables and rules for activities, seasonal affixes, weather, and story logs from the client’s Sheets/Discord into `reference/` specs so post-MVP build can start without ambiguity.

### Scope

- Produce `reference/*-SPEC.md` (or similar) covering: traveling/checkpoints, art/lit submission hooks, encounter/drop tables, weather, GM tailored responses, per-horse story log, seasonal Wildlife Report / quests / affixes
- Call out open questions for client sign-off
- NOT in scope: implementation of those systems

### Technical Notes

- Existing hints: Google Sheets roller links in CMS / `useLinkDictionary`; admin horse randomizer already ported as a pattern
- Deliverable unblocks [015] and [016]

### Acceptance Criteria

- [ ] Spec documents committed under `reference/`
- [ ] Each major subsystem has enough detail to implement without rediscovering Discord tribal knowledge
- [ ] Open questions explicitly listed
- [ ] Roadmap [015]/[016] Dependencies updated to this item when specs land

### Tests

- [ ] No automated tests. Deliverable is documentation. Verified by client sign-off on `reference/*-SPEC.md`.

---

## [015] Activities and Competitions System

**Status:** `freezer`
**Depends On:** [014]

### Goal

In-app traveling progression, art/lit submissions, admin/GM rolls, results, relationships/drops/encounters, and per-horse story logs — replacing Discord/Sheets play loop.

### Scope

- Full play loop per client spec from [014]
- Site-wide weather if specified
- NOT in scope until spec signed off

### Technical Notes

- **Blocked:** awaiting [014] client spec
- Ref doc Site Plan → Activities/Competitions

### Acceptance Criteria

- [ ] Spec from [014] accepted
- [ ] End-to-end submit → approve → roll → results → story log
- [ ] Pest coverage for core state transitions

### Tests

- [ ] `tests/Feature/ActivitySubmissionTest.php::it_moves_a_submission_through_submit_approve_roll_results`
- [ ] `tests/Feature/ActivitySubmissionTest.php::it_forbids_non_staff_from_approving_or_rolling`
- [ ] `tests/Feature/ActivityStoryLogTest.php::it_appends_results_to_the_per_horse_story_log`
- [ ] `tests/Feature/ActivityEncounterTest.php::it_applies_encounter_and_drop_tables_from_spec`

---

## [016] Seasonal Events In-App

**Status:** `freezer`
**Depends On:** [014]

### Goal

Move Wildlife Report, quests, and seasonal affixes from Discord-only into platform features that affect rolls/rewards.

### Scope

- Per [014] seasonal spec
- NOT in scope: Discord bot integration unless requested later

### Technical Notes

- **Blocked:** awaiting [014]
- Post-MVP per launch grill decisions

### Acceptance Criteria

- [ ] Staff can configure a season’s affixes/quests
- [ ] Affixes affect eligible rolls/rewards as specified
- [ ] Tests for affix application

### Tests

- [ ] `tests/Feature/SeasonalEventTest.php::it_lets_staff_configure_a_seasons_affixes_and_quests`
- [ ] `tests/Feature/SeasonalEventTest.php::it_applies_active_affixes_to_eligible_rolls`
- [ ] `tests/Feature/SeasonalEventTest.php::it_ignores_affixes_outside_the_season_window`

---

## [017] Opt-In PvP

**Status:** `freezer`
**Depends On:** [015]

### Goal

Opt-in player-vs-player gameplay after core activity systems exist (ref doc: implement once everything else is done).

### Scope

- Opt-in flag; challenge/steal/spar rules per future spec
- NOT in scope for MVP launch

### Technical Notes

- CMS `player-vs-player` page is documentation only today
- Feathers in welcome package are PvP-oriented — economy exists before PvP

### Acceptance Criteria

- [ ] Opt-in required before PvP participation
- [ ] Core PvP actions implemented per agreed rules
- [ ] Tests for opt-in enforcement

### Tests

- [ ] `tests/Feature/PvpTest.php::it_blocks_pvp_actions_for_users_who_have_not_opted_in`
- [ ] `tests/Feature/PvpTest.php::it_allows_core_pvp_actions_between_opted_in_users`
- [ ] `tests/Feature/PvpTest.php::it_lets_a_user_opt_back_out`

---

## [018] Automatic Inactivity Freeze

**Status:** `next`
**Depends On:** [015]
**Spec:** none

### Goal

Freeze accounts after 4 months without art/lit submissions; frozen accounts skip aging/events/PvP until unfrozen. Every automatic freeze surfaces in the admin submission queue so staff see it happen and can set the account back to active in one click.

### Scope

- Scheduler using submission activity (not login proxy)
- Enforce frozen state in aging/events/PvP
- Keep existing admin freeze + self-unfreeze
- Auto-frozen accounts surface as a notice in the admin Submissions tab, each with a "Set active" control that clears `frozen_at`
- Distinguish automatic freezes from manual ones so the notice only lists the former
- Dismissing or acting on the notice does not re-freeze the account on the next scheduler run until it is inactive again
- NOT in scope: inventing a login-based proxy; full activities system ([015]); email or in-app notifications to the frozen user

### Technical Notes

**User flows:**

- **Scheduler:** nightly command freezes accounts with no art/lit submission in 4 months and records the freeze as automatic.
- **Admin:** Submissions tab at `/admin` (submissions). A notice above the queue lists accounts auto-frozen since the last acknowledgement, showing the account name and the freeze date. Each row has a "Set active" button that unfreezes immediately and drops the row from the notice.
- **User:** existing profile self-unfreeze is unchanged.

**Details:**

- Manual freeze already: `frozen_at`, Admin Users tab (`resources/js/pages/admin/UsersTab.vue:258-263`), profile self-unfreeze
- `admin.users.unfreeze` (`routes/web.php:94`, `UserController::unfreezeUser`) already does the state change; the notice reuses that route rather than adding a second unfreeze path
- Telling automatic from manual needs a column or flag beyond `frozen_at` alone (e.g. `frozen_reason` or `frozen_automatically_at`); pick one when the scheduler is written
- Needs an acknowledgement marker so a set-active row stays gone and an ignored row persists across admin visits
- Notice is gated on `can:admin.submissions`, the same gate as the tab (`routes/web.php:70`)
- Unfrozen from post-MVP (2026-07-16); still gated on submission activity from [015]
- Can ship freeze _enforcement_ + scheduler skeleton before [015] if activity source is stubbed - confirm trigger strategy before coding

### Acceptance Criteria

- [ ] Accounts with no submissions for 4 months auto-freeze
- [ ] Frozen users excluded from aging/events/PvP
- [ ] Unfreeze restores eligibility
- [ ] Admin Submissions tab shows a notice listing auto-frozen accounts
- [ ] "Set active" from the notice unfreezes the account and removes it from the notice
- [ ] Manually frozen accounts do not appear in the notice
- [ ] Pest tests for scheduler, enforcement, and the admin notice

### Tests

- [ ] `tests/Feature/InactivityFreezeTest.php::it_freezes_accounts_with_no_submissions_for_four_months`
- [ ] `tests/Feature/InactivityFreezeTest.php::it_leaves_recently_active_accounts_unfrozen`
- [ ] `tests/Feature/InactivityFreezeTest.php::it_excludes_frozen_users_from_aging_events_and_pvp`
- [ ] `tests/Feature/InactivityFreezeTest.php::it_restores_eligibility_after_unfreeze`
- [ ] `tests/Feature/InactivityFreezeTest.php::it_lists_auto_frozen_accounts_in_the_admin_submission_queue`
- [ ] `tests/Feature/InactivityFreezeTest.php::it_omits_manually_frozen_accounts_from_the_notice`
- [ ] `tests/Feature/InactivityFreezeTest.php::it_sets_an_auto_frozen_account_back_to_active_from_the_notice`

---

## [020] Re-enable CI Test Suite and Align Environments

**Status:** `next`
**Depends On:** none
**Spec:** none

### Goal

GitHub Actions runs the Pest suite again, on the same PHP version and the same database engine used locally, so a green check means the suite actually passed. Style checks stop silently rewriting a throwaway checkout and start failing on violations instead. The one test that was commented out to make CI pass is restored.

### Scope

- Re-enable the commented `Tests` step in `.github/workflows/tests.yml`, and the commented `name:` key above it
- MySQL 8.4 service container with the database named `rattlesnake_mountain_testing`, and the job-level `DB_CONNECTION` / `DB_DATABASE` env removed so `phpunit.xml` is the only declaration
- Declare `gd`, `mbstring` and `pdo_mysql` on `setup-php`; drop `coverage: xdebug` to `none`
- Raise `composer.json` to `"php": "^8.4"`; correct `CLAUDE.md` on the PHP version and on nothing running in CI
- `.env.example` switches from sqlite to MySQL
- Pin `DEV_PASSWORD` to empty in `phpunit.xml`
- Restore `tests/Feature/DevPasswordTest.php` and fix every case
- `lint.yml` switches to check mode: `pint --test`, `npm run format:check`, `eslint .` with no `--fix`
- Triggers become pushes to `main` plus pull requests; the phantom `develop` entry goes
- NOT in scope: branch protection or required status checks. CI reports, it does not block merges (decision of 2026-09-11)
- NOT in scope: `vue-tsc` in CI. `npm run build` stays the frontend gate, and the two known `admin/Index.vue` type errors from [031] stay out of this item
- NOT in scope: coverage thresholds, running the suite against sqlite, consolidating the two workflow files

### Technical Notes

**User flows:**

- **Developer:** pull request on GitHub. The `linter` and `tests` checks both report a real result, and a failure is visible without blocking the merge.
- **Developer:** `php artisan test` locally. Same PHP 8.4, same MySQL engine, same `rattlesnake_mountain_testing` database name as CI.

**Details:**

- The suite has not run in CI since `a5566d5` ("temporarily disabling CI"). `tests.yml` comments out the `- name: Tests` step, so the job checks out, installs, builds assets and reports green without testing anything. Only the `name:` key at the top is commented, so the workflow still fires on every push. It is a check that proves nothing.
- PHP is already aligned in practice: local is 8.4.7 and both workflows pin 8.4. Only `composer.json:12` (`^8.3`, a floor rather than a pin) and `CLAUDE.md:31` ("PHP 8.3") still say otherwise. Raising the floor to `^8.4` sits above Laravel 13's own `^8.3` minimum without contradicting it.
- `CLAUDE.md` also states "Nothing here runs in CI", which `lint.yml` has contradicted all along.
- PHPUnit does not overwrite an environment variable that already exists unless the `<env>` entry carries `force="true"`. `tests.yml` sets `DB_CONNECTION=sqlite` and `DB_DATABASE=':memory:'` as job env, so those beat the `phpunit.xml` values and CI would test an engine this project has never run on. Removing the job env is the fix rather than adding `force`: one declaration, in the file CLAUDE.md already warns never to weaken.
- Naming the service database `rattlesnake_mountain_testing` means `phpunit.xml` needs no CI-specific branch. Host and credentials come from the copied `.env`, which is why `.env.example:24-29` moves to MySQL instead of staying on sqlite the project has never used.
- `config/image.php:4` selects the `gd` driver and three tests upload images (`HorseImageUploadTest`, `Settings/AvatarUploadTest`, `UploadLimitTest`), so the extension list is declared explicitly rather than trusted to the runner's defaults.
- `DevPasswordTest` is not purely an environment casualty. Its "bypasses protection when no dev password is set" case asserts `$page->component('static/Home')`, but `routes/web.php:221` renders `Welcome`, so that assertion is stale on any machine. Restoring the file means re-running all six cases and fixing each on its merits. Why CI rejected them in `82f8e1e` is still undiagnosed, and the diagnosis is part of this item.
- `DEV_PASSWORD` is commented out at `.env:7` and unpinned in `phpunit.xml`, so uncommenting one line in an untracked file would redirect every feature test through `DevPasswordProtection`. Pinning it empty makes the suite independent of local `.env` state; the restored cases set it per-test with `Config::set` as they already do.
- `lint.yml` runs `pint`, `npm run format` and `eslint --fix`, all of which rewrite files and exit 0, and the auto-commit step that would have pushed those rewrites is itself commented out. [031] already made all three pass and added a pre-commit hook, so check mode should be green on the first run.
- If a test proves genuinely unportable to Linux, use `->skip('<reason>, see [NNN]')` and open the item it names. Commenting a test out is what produced this one.
- The as-built note lists every test whose behaviour differed between the two environments. That list is criterion 7, and it lives here rather than in a separate document.

### Acceptance Criteria

- [ ] The Pest step is active in `tests.yml` and the full suite passes in CI against MySQL 8.4, on a push to `main` and on a pull request
- [ ] The same commit passes locally with `php artisan test`, and the two runs report the same test count
- [ ] `phpunit.xml` is the only place the test database is named, no workflow env overrides it, and the `DB_DATABASE` safety override is intact
- [ ] `DevPasswordTest` is restored with all six cases active and passing in both environments
      `tests/Feature/DevPasswordTest.php::it_shows_the_password_page_when_a_dev_password_is_set`
      `tests/Feature/DevPasswordTest.php::it_allows_access_with_the_correct_password`
      `tests/Feature/DevPasswordTest.php::it_denies_access_with_an_incorrect_password`
      `tests/Feature/DevPasswordTest.php::it_allows_access_to_all_routes_after_authentication`
      `tests/Feature/DevPasswordTest.php::it_bypasses_protection_when_no_dev_password_is_set`
      `tests/Feature/DevPasswordTest.php::it_redirects_from_the_password_page_when_no_dev_password_is_set`
- [ ] The suite passes with `DEV_PASSWORD` uncommented in a local `.env`, proving the pin works
- [ ] No test file in `tests/` is commented out, and any `->skip()` names both a reason and a roadmap item
- [ ] `lint.yml` fails on a deliberately misformatted file and passes on a clean tree
- [ ] `composer.json`, both workflows and `CLAUDE.md` state PHP 8.4, and `CLAUDE.md` no longer claims nothing runs in CI
- [ ] Every test that behaved differently between local and CI is listed in the as-built note with what was wrong

---

## [022] Lifecycle Health Roll Application

**Status:** `next`
**Depends On:** [004]

### Goal

On each lifecycle aging cycle, health rolls from `lifecycle_settings` add or subtract horse health based on injury state (once HP/injury exists), instead of settings-only deferred behavior left by [004].

### Scope

- Apply min/max health roll from Lifecycle settings during `horses:lifecycle` / Run now
- Direction of change depends on whether the horse is injured
- Persist current health (or equivalent) on the horse; log outcomes for support
- NOT in scope: full story-progression roller UI; PvP injury; redesigning lifespan modifiers beyond what’s needed for the roll

### Technical Notes

- Deferred from [004]: settings + UI already exist; runner intentionally skips apply
- Needs injury / HP model (likely with story progression) before coding
- Service: [`app/Services/LifecycleAgingService.php`](app/Services/LifecycleAgingService.php)
- Settings: `horse_auto_health_roll_min` / `horse_auto_health_roll_max` on `LifecycleSetting`

### Acceptance Criteria

- [ ] Lifecycle run updates living horses’ health within configured min/max using injury-aware +/- rules
- [ ] Uninjured / injured paths covered by Pest tests
- [ ] Outcomes logged for support
- [ ] Lifecycle UI no longer labels health rolls as deferred-only

### Tests

- [ ] `tests/Feature/LifecycleHealthRollTest.php::it_applies_a_health_roll_within_configured_min_and_max`
- [ ] `tests/Feature/LifecycleHealthRollTest.php::it_subtracts_health_for_injured_horses`
- [ ] `tests/Feature/LifecycleHealthRollTest.php::it_adds_health_for_uninjured_horses`
- [ ] `tests/Feature/LifecycleHealthRollTest.php::it_skips_dead_horses`
- [ ] `tests/Feature/LifecycleHealthRollTest.php::it_logs_each_health_outcome`

---

## [023] Advanced Breeding Lifecycle and Stone Modifiers

**Status:** `freezer`
**Depends On:** [005]

### Goal

Enforce full breeding season/estrus/checkpoint/attempt rules and allow Stones to modify breeding outcomes atomically.

### Scope

- Estrus, breeding season, checkpoint requirements, attempt limits, conception/failure odds
- Twins/rerolls and fuller foal outcome rolls (health/stats/traits) as specified
- Atomic Stone consumption during breeding for rare-gene modifiers
- NOT in scope until [005] MVP is live and client confirms remaining rules

### Technical Notes

- Builds on breeding requests/slots from [005]
- Item consumption overlaps [011]; coordinate before coding

### Acceptance Criteria

- [ ] Season/estrus/checkpoint/attempt rules enforced in-app
- [ ] Stones consume atomically when applied to a breeding
- [ ] Pest coverage for failure paths and item races

### Tests

- [ ] `tests/Feature/BreedingSeasonTest.php::it_rejects_breeding_outside_the_season_window`
- [ ] `tests/Feature/BreedingSeasonTest.php::it_enforces_estrus_and_attempt_limits`
- [ ] `tests/Feature/BreedingSeasonTest.php::it_requires_checkpoints_before_conception`
- [ ] `tests/Feature/BreedingStoneTest.php::it_consumes_a_stone_atomically_when_applied`
- [ ] `tests/Feature/BreedingStoneTest.php::it_does_not_consume_a_stone_when_breeding_fails`

---

## [024] Shared Genotype-to-Phenotype Reader

**Status:** `freezer`
**Depends On:** [005]

### Goal

Deterministic genotype → phenotype mapping reused by breeding results and the horse randomizer.

### Scope

- Canonical phenotype rules for supported loci (including Cream/Pearl interactions)
- Replace breeding phenotype placeholder and randomizer coat-label shortcuts
- NOT in scope: designer commission workflows; markings designer tools

### Technical Notes

- Intended shared service used by local Punnett provider and `HorseRandomizerService`
- Must stay compatible with `config/breeding.php` locus definitions

### Acceptance Criteria

- [ ] Supported genotypes produce stable phenotype labels
- [ ] Breeding and randomizer share the same reader
- [ ] Unit tests cover Cream/Pearl and base coat cases

### Tests

- [ ] `tests/Unit/PhenotypeReaderTest.php::it_maps_base_coat_genotypes_to_stable_labels`
- [ ] `tests/Unit/PhenotypeReaderTest.php::it_resolves_cream_and_pearl_interactions`
- [ ] `tests/Unit/PhenotypeReaderTest.php::it_matches_locus_definitions_from_config_breeding`
- [ ] `tests/Feature/BreedingTest.php::it_uses_the_shared_reader_for_foal_phenotypes`
- [ ] `tests/Feature/AdminHorseRandomizerTest.php::it_uses_the_shared_reader_for_coat_labels`

---

## [038] Site Settings Tab and Site Resources Library

**Status:** `next`
**Depends On:** [037] (done, see ROADMAP_DONE.md)
**Spec:** none

### Goal

Admin gets a single Site Settings tab holding pages, menu and site resources. Resources lists the site's non-character images, accepts uploads, and feeds an image picker in the page editor. The home page becomes a CMS page so it is editable like any other, without changing how it looks.

### Scope

- New "Site settings" admin tab; the CMS tab's page list and menu builder fold into it as cards
- Site resources card: lists `public/assets/` uploads and tracked `public/images/`, uploads, deletes uploads
- Upload pipeline: WebP conversion with a dimension cap for png/jpg, GIF passthrough, SVG sanitized on upload
- Delete scans `cms_pages.content` for the filename and names every page using it before confirming
- Image picker in the [037] editor, sourced from the library
- Convert `/` to a CMS page with slug `home`; hero stays app chrome, CTA and feature grid become boxes styled to match
- NOT in scope: artist attribution on assets ([039]); horse and character images, which stay on the `public` disk and never appear here

### Technical Notes

**User flows:**

- **Admin:** "Site settings" tab at `/admin`. Cards for Pages, Menu and Site resources.
- **Admin:** Site resources card. Upload an image, copy its path, delete an upload.
- **Admin:** image button in the page editor toolbar. Picks from the library or uploads inline.
- **Admin:** cog on `/`. Edits home exactly like any other CMS page.

**Details:**

- Uploads go to `public/assets/`, gitignored. The 75 files in `public/images/` are tracked and stay read-only in the library, since deleting a committed file from a running server only desynchronises it from the repo. Both directories are listed by a filesystem scan; there is no media table.
- That scan means no uploader attribution, no upload timestamp and no usage index. [039] revisits this, and adding artist data will almost certainly require a table. Do not build schema here in anticipation of it.
- Horse uploads at `HorseController.php:508` write WebP to the `public` disk under `horse-images/`. Site assets follow the same conversion approach but a different destination, and the library must never surface the horse directory.
- SVG is the only real XSS surface in this item: served from your own origin, an unsanitized SVG runs with the viewer's session. Strip `script`, event attributes and external references on upload, or reject the file.
- Delete safety is a `LIKE` query against `cms_pages.content` for the filename, cheap at this page count. It warns, it does not block.
- Home conversion: `Welcome.vue:88-128` already uses a three-column grid with `col-span-1/2/3`, so the CTA and feature grid map onto boxes directly. The hero stays in the component as app chrome. Home cannot be hidden or deleted, per [036].
- `public/assets/` needs a deploy note: it is outside git, so it is not restored by a Forge deploy and needs its own backup.

### Acceptance Criteria

- [ ] The resources card lists both upload and tracked directories and excludes horse and character images
      `tests/Feature/Cms/SiteResourcesTest.php::it_lists_uploads_and_tracked_site_images`
      `tests/Feature/Cms/SiteResourcesTest.php::it_excludes_horse_images_from_the_library`
- [ ] png and jpg uploads are converted to WebP within the dimension cap, GIFs are stored untouched, and SVGs are stripped of scripts and event handlers
      `tests/Feature/Cms/SiteResourceUploadTest.php::it_converts_raster_uploads_to_webp`
      `tests/Feature/Cms/SiteResourceUploadTest.php::it_stores_gifs_without_conversion`
      `tests/Feature/Cms/SiteResourceUploadTest.php::it_strips_scripts_from_uploaded_svgs`
- [ ] Oversized files and disallowed types are rejected
      `tests/Feature/Cms/SiteResourceUploadTest.php::it_rejects_oversized_and_disallowed_uploads`
- [ ] Deleting an upload names every page whose content references it, and tracked `public/images/` files cannot be deleted
      `tests/Feature/Cms/SiteResourcesTest.php::it_reports_pages_using_an_asset_before_deletion`
      `tests/Feature/Cms/SiteResourcesTest.php::it_refuses_to_delete_a_tracked_site_image`
- [ ] A user without `admin.cms` cannot list, upload to, or delete from the library
      `tests/Feature/Cms/SiteResourcesTest.php::it_forbids_library_access_without_the_cms_capability`
- [ ] Home resolves to the `home` CMS page and is editable through the same endpoints as any other page
      `tests/Feature/Cms/HomePageConversionTest.php::it_renders_home_from_the_cms_page`
      `tests/Feature/Cms/HomePageConversionTest.php::it_saves_edits_to_the_home_page`
- [ ] Home looks unchanged after conversion at desktop and phone widths, confirmed in a browser
- [ ] The Site settings tab, its three cards and the editor's image picker work end to end, confirmed in a browser

---

## [039] Attributed Site Image Uploads

**Status:** `freezer`
**Depends On:** [038]
**Spec:** none

### Goal

Site artwork carries structured artist attribution again. Uploading a site image captures the artist's name and link alongside the file, the credits archived by [036] are re-imported onto the matching files, and credits render from that data rather than from hand-typed caption text.

### Scope

- Storage for artist name and link per site asset
- Upload surface capturing attribution, modelled on the character upload flow but not character-specific
- Re-import the archived `images` arrays from the [036] fixture onto matching paths
- Credits render from the stored data wherever the asset is used
- NOT in scope: attribution for horse and character images

### Technical Notes

**Blocked:** three decisions are open and the grill was cut short before they were settled (2026-09-10).

- **Where attribution lives.** [038] deliberately ships a filesystem scan with no media table. Structured credits need somewhere to live: a `media` table covering uploads with a scan still handling tracked files, a `media` table covering everything via a one-time import of the 75 files in `public/images/`, or a sidecar JSON that keeps the no-table decision at the cost of querying and merge conflicts.
- **What the page is.** Described as "like the character page, but not character specific". That reads as either an admin-only upload surface with metadata, or that plus a public gallery of site artwork with credits, which adds design and moderation surface.
- **How existing credits are matched.** The archive keys attribution by image path. Paths rewritten during the [036] conversion, or images an admin replaced between [036] and this item, will not match and need a manual pass.

**Details:**

- No attribution model exists anywhere in the codebase today. Horses carry no artist column; the CMS `images` arrays are the only structured credits that have ever existed, and [036] retires them into a fixture archive.
- Until this item lands, credits are caption text inside box HTML: visible on the page, not queryable, and editable into anything by whoever is editing.
- The archive written by the [036] migration is the only structured source for the original credits. It must survive until this item consumes it.

### Acceptance Criteria

- [ ] Uploading a site image captures artist name and link and stores them with the file
- [ ] The archived credits from [036] are re-imported onto the matching assets, and unmatched entries are reported rather than dropped
- [ ] Credits render from stored attribution wherever an attributed asset is used
- [ ] Horse and character images are untouched by this system
