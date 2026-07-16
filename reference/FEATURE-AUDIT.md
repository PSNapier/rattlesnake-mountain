# Feature Audit — Rattlesnake Mountain

**Date:** 2026-07-16  
**Sources:** Working tree on `main`, [ref doc](https://docs.google.com/document/d/1lzUROVRdhBtTQPaNg5RJbK-L3VvIGeJ0MKbT1BkydCs/edit), [`.cursor/burndown.md`](../.cursor/burndown.md)

Status values: **IMPLEMENTED** | **PARTIAL** | **MISSING**

---

## Summary

| Category | Approx. status |
| --- | --- |
| Auth & account basics | ~85% (welcome package, referral rewards missing) |
| Public lore / CMS pages | ~80% (editable CMS; Home hardcoded; JSON multi-box editor) |
| Horse/herd registry + approval | ~70% (CRUD + queue + inbox; no terms/graveyard/claimables) |
| Economy (items, shop, inventory) | ~60% (catalog + shop + user inventory; no equip/use/trade) |
| Admin tooling | ~55% (tabs exist; lifecycle config-only; staff roles cosmetic) |
| ARPG gameplay (activities, breeding, PvP, seasonal) | ~5% (docs + admin randomizer only) |

---

## 1. Spec Feature Matrix

| # | Spec Feature | Status | Evidence / Gap |
| --- | --- | --- | --- |
| 1a | Email verification | **IMPLEMENTED** | `MustVerifyEmail`; `routes/auth.php`; optional skip via `config('app.skip_email_verification')` + `SkipEmailVerification` middleware |
| 1b | Rules-agreement checkbox | **IMPLEMENTED** | `Register.vue` + `RegisteredUserController` validates `rules_agreed` |
| 1c | Referred-by username | **IMPLEMENTED** (storage only) | `users.referred_by_username`; no reward logic |
| 1d | Welcome package grant | **MISSING** | Spec in burndown + CMS Getting Started; no grant on register. `ItemSeeder` only seeds Scorpion + White-Modifier Stone |
| 2 | Account freeze/unfreeze | **PARTIAL** | Manual admin freeze + user self-unfreeze (`frozen_at`). No auto-freeze; frozen state does not block gameplay |
| 3 | Horse auto-aging / health / NPC death | **PARTIAL** | `LifecycleSetting` + Admin Lifecycle tab save settings. No scheduled job. NPC deaths UI is placeholder |
| 4 | Password recovery | **IMPLEMENTED** | Forgot/reset pages + `PasswordResetTest` |
| 5a | Editable bio / avatar | **IMPLEMENTED** | User profile dashboard; `Appearance.vue` avatar upload |
| 5b | Herd-leader onboarding prompt | **MISSING** | Optional `herd_leader_id` on herd create only |
| 6 | Randomized claimable horses | **MISSING** | Documented in CMS; Discord workflow. Admin randomizer is NPC stat roller, not claimable picker |
| 7a | Design image upload | **IMPLEMENTED** | `HorseController::uploadImage` + `ImageUpload.vue` |
| 7b | Upload terms agreement | **MISSING** | No checkbox on `Horses/Create.vue` |
| 7c | Graveyard / free-design disposition | **MISSING** | No fields or UI |
| 7d | Admin design approval | **IMPLEMENTED** | Submissions tab: contact / archive / unarchive / approve / publish; user inbox accept/decline |
| 8 | Horse page vs herd page | **PARTIAL** | Both exist; limited game UI (no story log, equip management) |
| 9a | Item catalog + admin CRUD | **IMPLEMENTED** | `Item`, `user_items`, Admin Items tab |
| 9b | User inventory UI | **IMPLEMENTED** | `InventoryController`, `Inventory/Index.vue` |
| 9c | Horse/herd inventory & equipment UI | **PARTIAL** | JSON columns; show pages display counts only |
| 9d | Item use / equip / dequip | **MISSING** | `uses_per_unit` on items unused in gameplay |
| 10 | Activities / competitions / story log / weather | **MISSING** | CMS lore only; Google Sheets / Discord |
| 11 | Horse randomizer roller | **IMPLEMENTED** | `HorseRandomizerService` + `config/horse-randomizer/` + Admin Rollers tab |
| 12 | Seasonal events / PvP | **MISSING** | CMS pages only |
| 13a | Shop (Trash and Trinkets) | **IMPLEMENTED** | Browse + purchase with Scorpions; admin listings |
| 13b | Player trading | **MISSING** | — |
| 13c | Breeding | **MISSING** | Genetics config exists for randomizer; no Punnett breeding |
| 14 | Leaderboard | **IMPLEMENTED** | Top 20: most horses, largest herds, most Scorpions |
| 15 | Announcements | **MISSING** | Home news hardcoded Sept 2023 in `Welcome.vue` |
| 16a | Admin users tab | **IMPLEMENTED** | Freeze/ban/role/delete → Sanctuary |
| 16b | Admin lifecycle tab | **PARTIAL** | Settings only; no runtime |
| 16c | Admin submissions + unarchive | **IMPLEMENTED** | — |
| 16d | Admin CMS tab | **PARTIAL** | Multi-box JSON editor; Home not editable; no rich text |
| 16e | Staff role levels | **PARTIAL** | Enum has Designer/StoryAdmin/GameMaster; only `Admin` passes `access-admin` |
| 17 | User inbox | **IMPLEMENTED** | Messages tied to horse submissions; accept/decline admin edits |

---

## 2. Routes / Pages Inventory (high level)

### Public
- `/` → `Welcome.vue` (hardcoded; not CMS)
- CMS-backed: getting-started, rules, lore, character-handbook, stats-leveling, character-upload, shop, wildlife, lifespans, story-progression, claiming-npcs, herd-unity, breeding-foaling, player-vs-player, contact-us, privacy-policy
- `/leaderboard`, `/dev-password`

### Auth
- Register, login, forgot/reset password, email verification, confirm password

### Authenticated
- Dashboard (`Users/Index` profile), herds CRUD, horses CRUD, inventory, inbox, settings (profile / password / appearance), public profiles under `/u/{user}`

### Admin (`can:access-admin`)
- Tabs: submissions, rollers, users, items, shop, lifecycle, cms

---

## 3. Test Coverage Gaps (new / incomplete features)

| Feature | Tests? | Gap |
| --- | --- | --- |
| Registration rules + referrer | Yes | No skip-verification path |
| Welcome package | No | — |
| Frozen enforcement | No | Only admin freeze/unfreeze |
| Lifecycle automation | No | Settings save only (`AdminLifecycleTest`) |
| Horse randomizer | Yes | — |
| Shop | Yes | — |
| Leaderboard | Yes | — |
| CMS | Partial | Create only; no update/reorder/menu |
| Inbox | Partial | Accept only |
| Bio / avatar | Partial | Path traversal only for avatars |
| User inventory / admin items | No | — |
| Staff role gates | No | — |
| Referral rewards | No | — |

---

## 4. External Dependencies (still Discord / Sheets)

Welcome packages, claimable rolls, breeding forms, story progression rolls, seasonal affixes, PvP, and trading currently described as Discord workflows in CMS content and the ref doc. In-app replacements are tracked in `ROADMAP.md`.
