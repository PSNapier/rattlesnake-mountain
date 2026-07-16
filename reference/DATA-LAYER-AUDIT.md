# Data Layer Audit — Rattlesnake Mountain

**Date:** 2026-07-16  
**Sources:** `app/Models`, `database/migrations`, factories/seeders, `routes/console.php`, enums

---

## 1. Eloquent Models

| Model | Purpose |
| --- | --- |
| `User` | Auth, role, bio, avatar, `referred_by_username`, `frozen_at`, ban fields, last login |
| `Horse` | Character record + pending/public workflow (`state`, `public_horse_id`, archive/contact timestamps) |
| `Herd` | Owner, leader, members JSON, inventory/equipment JSON |
| `Item` | Catalog item (`name`, `max_count`, `uses_per_unit`, `is_active`) |
| `ShopListing` | Shop catalog row linked to items + flavor text |
| `CharacterImage` | Dashboard PNG uploads (soft-delete + prune) |
| `AdminSubmissionLog` | Audit trail for horse submission actions |
| `Message` / `MessageComment` | User inbox threads for admin↔owner submission communication |
| `CmsPage` | Slug + JSON multi-box content |
| `MenuItem` | Nav menu tree |
| `LifecycleSetting` | Singleton-style config for aging schedule (no runner) |

**Not present:** Announcement, Trade/Offer, Breeding record, Activity, StoryLog, Event, Graveyard disposition, claimable-horse pool.

---

## 2. Spec Field Checklist

| Field / Concept | Status | Where |
| --- | --- | --- |
| User `role` | Yes | `Role` enum: user, admin, designer, story_admin, game_master |
| User `referred_by_username` | Yes | Column only; no reward logic |
| User `frozen_at` / ban | Yes | Manual admin; no auto-freeze |
| User `dateLastSeen` | Partial | Login listener `RecordLastLogin` (not inactivity-by-submission) |
| Horse `age`, `bred_by`, `bloodline`, `progeny`, `stats`, `geno`, `herd_id` | Yes | `horses` table |
| Horse `design` | Partial | `design_link` + image upload path; no terms/graveyard |
| Horse/Herd `inventory` / `equipment` | Yes | JSON columns; no relational item FKs on horse |
| User inventory | Yes | `user_items` pivot with quantities |
| Item catalog | Yes | `items` + shop listings |
| Lifecycle settings | Yes | Table; **no scheduled execution** |
| CMS pages | Yes | DB-driven except Home (`Welcome.vue`) |
| Announcement | **No** | — |
| Activity / weather / story log | **No** | — |
| Trade | **No** | — |
| Breeding offspring records | **No** | Fields exist; no breeding flow |

---

## 3. Enums

| Enum | Cases |
| --- | --- |
| `App\Models\Role` | User, Admin, Designer, StoryAdmin, GameMaster |
| `App\Enums\HorseState` | Public, Pending |
| `App\Enums\AdminAction` | Contacted, Approved, Archived |

Authorization: `Gate::define('access-admin')` returns `$user->isAdmin()` only — non-admin staff roles are cosmetic until gates are expanded.

---

## 4. Migrations (domain-relevant)

- Users: role, referred_by, bio, avatar, freeze/ban management fields
- Herds / Horses (core ARPG fields + state/approval timestamps)
- Admin submission logs
- Character images
- Messages + comments
- Items + `user_items`
- Shop listings (+ flavor text, uses_per_unit on items)
- CMS pages + menu items
- Lifecycle settings

---

## 5. Factories & Seeders

**Factories:** User, Horse, Herd, CharacterImage (typical). AdminSubmissionLog / Item / ShopListing / Message coverage varies — check before writing tests.

**Seeders of note:**
- `ItemSeeder` — Scorpion, White-Modifier Stone only
- `ShopCatalogSeeder` — broader shop catalog
- `CmsPageSeeder` — lore pages including welcome-package and claimable docs
- `SanctuarySeeder` — transfer target for deleted users
- `HerdHorseSeeder` — local herd/horse fixtures

Welcome-package items (Cream/Pearl stones, randomized stones, herbs, feathers) are **not fully seeded** in `ItemSeeder`; grant logic must ensure catalog rows exist.

---

## 6. Scheduled Tasks & Jobs

| Task | Schedule | Purpose |
| --- | --- | --- |
| `model:prune` CharacterImage | Daily | Soft-deleted images >30 days |

**Empty:** `app/Console/Commands/`, `app/Jobs/` — no aging, freeze, breeding, or trade jobs.

---

## 7. Genetics / Randomizer Config

Reusable for breeding (`[005]`):

- `app/Services/HorseRandomizerService.php`
- `config/horse-randomizer/genetics.php` (loci, modifiers, base coats)
- Also: `breed-age-rank.php`, `benefits.php`, `detriments.php`, `health.php`, `markings.php`

Randomizer currently generates standalone NPC rolls; breeding needs Punnett-square inheritance from two parent `geno` strings writing `bloodline` / `progeny`.

---

## 8. Architecture Notes

1. **Dual inventory models:** user inventory is relational (`user_items`); horse/herd inventory is schemaless JSON — equip/use workflows must reconcile or migrate.
2. **Submission workflow is horse-centric:** pending rows shadow public horses; inbox applies whitelisted admin edits.
3. **CMS is multi-box JSON**, not rich text; Home bypasses CMS entirely.
4. **Only automated lifecycle** is character-image pruning; ARPG aging is config-without-runner.
5. **`herd_leader_id`** lacks a migration FK in some paths — integrity often via form `exists:horses,id` only.
