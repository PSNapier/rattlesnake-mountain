# Backlog

Product and engineering backlog for this repo. Use it to prioritize work and give agents context; it is not a substitute for issues or a formal roadmap. Update sources when items are completed or reprioritized.

**Sources:** `todo-26-3-5.md`, `README.md`, codebase analysis.

---

## Known bugs

- [ ] **Guest visibility**: Some herd/horse pages are fully public. Only static/login/register pages should be visible to unauthenticated visitors.
- [ ] **Local vs CI test discrepancies**: Tests behave differently between local and CI environments.

## Planned features

Priorities are relative within this document only; align with team process (milestones, issues) before implementation.

### High priority — core game loop

- [ ] **Welcome package**: New players should automatically receive 500 Scorpions, 1 White-Modifier Stone, 1 Cream/Pearl Stone, 2 Randomized Stones, 2 Random Herbs, 2 Random Feathers.
- [ ] **Recruit-a-friend**: `referred_by_username` field exists on User model but no reward logic. Both recruiter and recruit get bonus items (100 Scorpions, 2 chosen Stones, 2 chosen Herbs, 2 chosen Feathers, +10 Scorpions/submission for 3 months, +1 stat/submission for 3 months).
- [ ] **Lifespan / auto-aging**: `lifecycle_settings` table exists with config fields. No scheduled automation yet. Horses should auto-age every 4 IRL months. Health increases/decreases should be automated. Death rolls for old NPC horses.
- [ ] **Freeze inactive accounts**: Manual freeze exists. No automatic freeze after 4 months of inactivity (no art/lit submissions). Frozen accounts should not age or participate in events/PvP.

### Medium priority — gameplay systems

- [ ] **Breeding**: Horses should be breedable. No implementation yet. Bloodline and progeny JSON fields exist on Horse model.
- [ ] **Horse equipment (equip/dequip)**: `equipment` JSON column exists on horses and herds. No UI or logic.
- [ ] **Activities / competitions**: Story progression via "Traveling" from location to location (art/literature submissions). Horse relationships, item drops, weather events, horse encounters. Option for game master tailored responses. Story log per horse (BG3-style).
- [ ] **Seasonal events**: Wildlife Report, 2-3 Quests, 2-4 seasonal affixes affecting rolls/rewards. Currently Discord-only.

### Lower priority — community and economy

- [ ] **Market / shop**: "Trash and Trinkets" admin-manageable shop. Item cards with title, price, subtitle, description, usage count.
- [ ] **Player trading**: Transfer items between users. No implementation.
- [ ] **PvP**: Player vs. Player, opt-in. Explicitly noted as post-everything-else.
- [ ] **Leaderboard**: First pass exists. May need refinement.

## Technical debt

- [ ] **AdminController is 614 lines**: God controller handling submissions, items, users, CMS, lifecycle, and rollers. Should be split into focused controllers (AdminSubmissionController, AdminItemController, AdminUserController, AdminCmsController, etc.).
- [ ] **Staff roles unused**: `Designer`, `StoryAdmin`, `GameMaster` roles exist in enum but have no gate or policy. `access-admin` gate only allows `Role::Admin`. These roles cannot access any admin functionality.
