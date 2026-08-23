# Roadmap

<!-- Next task number: [025] -->

## [006] Player Trading

**Status:** `next`
**Depends On:** none

### Goal

Players can offer and accept simple item transfers using the existing `user_items` inventory, replacing Discord `#trading-post` for basic trades.

### Scope

- Offer create / accept / decline / cancel between two users
- Atomic quantity transfer with max_count enforcement
- Basic trade history for both parties
- NOT in scope: horse trading, auction house, Scorpion-only shop (already exists), escrow disputes UI beyond cancel/decline

### Technical Notes

- Inventory: `Item` + `user_items` pivot; [`InventoryController`](app/Http/Controllers/InventoryController.php)
- New models likely: `Trade` / `TradeItem` (or equivalent)
- Security: authorize ownership, prevent negative qty races, rate-limit offers

### Acceptance Criteria

- [ ] User A can offer items to User B; B can accept or decline
- [ ] Accept moves quantities atomically; inventories never go negative
- [ ] Cancel works for open offers; accepted trades immutable
- [ ] Pest tests cover happy path, insufficient qty, and unauthorized accept

### Tests

- [ ] `tests/Feature/TradeTest.php::it_creates_an_offer_between_two_users`
- [ ] `tests/Feature/TradeTest.php::it_transfers_quantities_atomically_on_accept`
- [ ] `tests/Feature/TradeTest.php::it_rejects_an_offer_exceeding_owned_quantity`
- [ ] `tests/Feature/TradeTest.php::it_forbids_a_third_party_from_accepting`
- [ ] `tests/Feature/TradeTest.php::it_cancels_open_offers_and_freezes_accepted_trades`

---

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

**Status:** `next`
**Depends On:** none

### Goal

Players without a herd leader are prompted on the dashboard toward character creation, randomizer/claimable path, stats guidance, and horse-lines download.

### Scope

- Detect “no herd leader yet” (no herd or herd without leader horse)
- Dashboard prompt with clear next steps and links
- Dismissible or persistent until leader exists (product choice — prefer persistent until complete)
- NOT in scope: full wizard multi-step SPA; implementing claimable roller ([010]) beyond linking

### Technical Notes

- Dashboard currently [`Users/Index`](resources/js/pages/Users/Index.vue) via dashboard route
- Link targets: character handbook / stats-leveling CMS pages, horses create, claimable flow when ready

### Acceptance Criteria

- [ ] New users without herd leader see onboarding prompt on dashboard
- [ ] Users with an established herd leader do not see the prompt
- [ ] Links resolve to real routes/pages
- [ ] Pest or browser-level assertion for prompt visibility conditions

### Tests

- [ ] `tests/Feature/OnboardingPromptTest.php::it_shows_the_prompt_for_users_without_a_herd_leader`
- [ ] `tests/Feature/OnboardingPromptTest.php::it_hides_the_prompt_once_a_herd_leader_exists`
- [ ] `tests/Feature/OnboardingPromptTest.php::it_links_only_to_resolvable_routes`

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

## [011] Item Usage and Equipment Workflows

**Status:** `next`
**Depends On:** none

### Goal

Players can equip/dequip items on horses (and use consumables where `uses_per_unit` applies), bridging user inventory and horse/herd equipment JSON.

### Scope

- Equip / dequip UI on horse (and optionally herd) pages
- Consume/use flow decrementing `user_items` or horse inventory consistently
- Respect `uses_per_unit` and `max_count`
- NOT in scope: full crafting; shop purchase (done); trading ([006])

### Technical Notes

- Dual model problem: relational `user_items` vs horse/herd JSON — pick one source of truth for equipped gear (document in implementation)
- Columns: `horses.inventory`, `horses.equipment`, `herds.*`; item `uses_per_unit` migration already exists
- Show pages currently display counts only

### Acceptance Criteria

- [ ] Player can move an owned item onto a horse’s equipment and back to inventory
- [ ] Consumable use decrements quantity / uses correctly
- [ ] Unauthorized users cannot equip on others’ horses
- [ ] Pest tests for equip, dequip, and consume

### Tests

- [ ] `tests/Feature/ItemEquipTest.php::it_moves_an_owned_item_onto_a_horse`
- [ ] `tests/Feature/ItemEquipTest.php::it_returns_equipped_gear_to_inventory_on_dequip`
- [ ] `tests/Feature/ItemEquipTest.php::it_decrements_uses_per_unit_when_consuming`
- [ ] `tests/Feature/ItemEquipTest.php::it_enforces_max_count_on_equip`
- [ ] `tests/Feature/ItemEquipTest.php::it_forbids_equipping_on_another_users_horse`

---

## [012] Light Mode Only and Legibility Pass

**Status:** `next`
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

- Settings Appearance currently mixes avatar + appearance: [`resources/js/pages/settings/Appearance.vue`](resources/js/pages/settings/Appearance.vue)
- Widespread `dark:` classes in Vue components — audit systematically
- Home CTAs: [`resources/js/pages/Welcome.vue`](resources/js/pages/Welcome.vue)

### Acceptance Criteria

- [ ] No user-facing dark/theme toggle; app renders consistently in light mode
- [ ] Documented list of pages checked for contrast issues; critical failures fixed
- [ ] Obsolete ToyHouse / rattlesnake-admin promotional links removed or replaced per product decision
- [ ] Smoke test: key public + auth pages render without theme flash to dark

### Tests

- [ ] `tests/Feature/LightModeTest.php::it_renders_key_pages_without_a_theme_toggle`
- [ ] `tests/Feature/LightModeTest.php::it_omits_obsolete_toyhouse_and_admin_account_links_from_home`

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

### Goal

Freeze accounts after 4 months without art/lit submissions; frozen accounts skip aging/events/PvP until unfrozen.

### Scope

- Scheduler using submission activity (not login proxy)
- Enforce frozen state in aging/events/PvP
- Keep existing admin freeze + self-unfreeze
- NOT in scope: inventing a login-based proxy; full activities system ([015])

### Technical Notes

- Manual freeze already: `frozen_at`, Admin Users tab, profile self-unfreeze
- Unfrozen from post-MVP (2026-07-16); still gated on submission activity from [015]
- Can ship freeze _enforcement_ + scheduler skeleton before [015] if activity source is stubbed — confirm trigger strategy before coding

### Acceptance Criteria

- [ ] Accounts with no submissions for 4 months auto-freeze
- [ ] Frozen users excluded from aging/events/PvP
- [ ] Unfreeze restores eligibility
- [ ] Pest tests for scheduler and enforcement

### Tests

- [ ] `tests/Feature/InactivityFreezeTest.php::it_freezes_accounts_with_no_submissions_for_four_months`
- [ ] `tests/Feature/InactivityFreezeTest.php::it_leaves_recently_active_accounts_unfrozen`
- [ ] `tests/Feature/InactivityFreezeTest.php::it_excludes_frozen_users_from_aging_events_and_pvp`
- [ ] `tests/Feature/InactivityFreezeTest.php::it_restores_eligibility_after_unfreeze`

---

## [019] CMS Rich Text / WYSIWYG and Home Editability

**Status:** `freezer`
**Depends On:** none

### Goal

Replace multi-box JSON CMS editing with a simpler rich-text (or in-page WYSIWYG) experience, and make Home editable once that direction is chosen.

### Scope

- Product decision: rich-text body vs on-page WYSIWYG
- Home (`Welcome.vue`) becomes CMS-managed or section-editable
- NOT in scope until decision made (burndown blocked items)

### Technical Notes

- Admin: [`resources/js/pages/admin/CmsTab.vue`](resources/js/pages/admin/CmsTab.vue) — `contentJson` multi-box
- Home intentionally excluded from `CmsPageSeeder` historically

### Acceptance Criteria

- [ ] Editing direction decided and documented
- [ ] CMS pages editable via chosen editor
- [ ] Home content editable without deploy
- [ ] Tests for update + public render

### Tests

- [ ] `tests/Feature/AdminCmsPageTest.php::it_saves_rich_text_bodies_from_the_chosen_editor`
- [ ] `tests/Feature/AdminCmsPageTest.php::it_updates_home_content_without_a_deploy`
- [ ] `tests/Feature/CmsStaticPageTest.php::it_renders_edited_content_publicly`

---

## [020] Fix Local vs CI Test Discrepancies

**Status:** `freezer`
**Depends On:** none

### Goal

Tests behave the same locally and in CI so failures are trustworthy.

### Scope

- Identify environment-specific failures (filesystem, case sensitivity, mail, vite, DB)
- Align phpunit/pest config, env, and path assumptions
- NOT in scope: expanding coverage for unrelated features

### Technical Notes

- Noted in [`.cursor/burndown.md`](.cursor/burndown.md)
- Windows vs Linux path/case issues have bitten production before (Inertia path case)

### Acceptance Criteria

- [ ] Documented list of previously divergent tests now passing in both environments
- [ ] CI green on main with same suite run locally
- [ ] No skipped/commented tests left as silent CI workarounds without tickets

### Tests

- [ ] Full suite green locally and in CI on the same commit. No new test file. Verified by comparing both runs.

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

