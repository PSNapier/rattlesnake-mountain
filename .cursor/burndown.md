# Burn Down 🔥

Product and engineering backlog for this repo. Use it to prioritize work and give agents context; it is not a substitute for issues or a formal roadmap. Update sources when items are completed or reprioritized.

**Sources:** `todo-26-3-5.md`, [ref doc](https://docs.google.com/document/d/1lzUROVRdhBtTQPaNg5RJbK-L3VvIGeJ0MKbT1BkydCs/edit?tab=t.0), codebase analysis.

---

## Low-Hanging Fruit

- [ ] **Implement welcome package rewards**: On new player creation, grant 500 Scorpions, 1 White-Modifier Stone, 1 Cream/Pearl Stone, 2 Randomized Stones, 2 Random Herbs, and 2 Random Feathers.
- [ ] **Enable non-admin staff roles in authorization**: Add gates/policies for `Designer`, `StoryAdmin`, and `GameMaster` roles so they can access intended admin features.

## Planables

- [ ] **Implement recruit-a-friend rewards**: Add referral reward logic using `referred_by_username` so recruiter and recruit both receive configured bonuses.
- [ ] **Implement lifespan/auto-aging automation**: Use `lifecycle_settings` to schedule horse aging every 4 IRL months, apply health changes, and run death rolls for old NPC horses.
- [ ] **Implement automatic inactive-account freeze**: Freeze accounts after 4 months without art/lit submissions and block aging/events/PvP while frozen.
- [ ] **Implement breeding system**: Add horse breeding using existing bloodline/progeny model fields.
- [ ] **Implement horse equipment workflows**: Add equip/dequip UI and logic for horse/herd `equipment` JSON data.
- [ ] **Implement activities/competitions system**: Add traveling progression, submission hooks, relationships, drops, weather/events, encounters, and horse story logs.
- [ ] **Implement seasonal event system in-app**: Move Wildlife Report/Quests/seasonal affixes from Discord-only to platform features.
- [ ] **Implement player trading**: Allow item transfers between users.
- [ ] **Implement PvP system (opt-in)**: Add player-vs-player gameplay after core systems are in place.
- [ ] **Evaluate/implement on-page WYSIWYG editing mode**: Consider page-level edit mode with in-context editing as a future CMS enhancement.
- [ ] **Fix local vs CI test discrepancies**: Tests currently behave differently between local and CI environments.

## Blocked

- [ ] **Refactor CMS page content input to rich text**: Replace current multi-box page input flow with a simpler rich text editing experience. _Blocked until I decide if I want to implement the in place WYSIWYG._
- [ ] **CMS Home page editability**: Home page is not editable in CMS while other pages are. _Blocked until I decide if I want to implement the in place WYSIWYG._
- [ ] **Refactor site styling for long-term maintainability**: Tighten visual consistency and simplify future style updates. _Blocked until MVP is more fully finalized._
