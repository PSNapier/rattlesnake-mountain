# Rattlesnake Mountain — Architecture Map

**Snapshot: 2026-09-07.** Point-in-time reacquaintance aid, not a maintained contract. Diagrams describe shipped code; dashed nodes tagged `[NNN]` are roadmap items that are **not built yet**.

Stack: Laravel 12 + Inertia + Vue 3 + Tailwind, MySQL. Herd locally, Forge in production.

---

## 1. Overview — entities and the actions that move them

Nodes are entities. Edges are the player or staff actions that create or change them. Cardinality is noted where it matters.

```mermaid
flowchart TD
    classDef gap stroke-dasharray: 6 4,stroke-width:2px

    Visitor([Visitor])
    User[User]
    Referral[Referral]
    Herd[Herd]
    Horse[Horse]
    Slot[BreedingSlot]
    Transfer[BreedingSlotTransfer]
    Req[BreedingRequest]
    Item[Item]
    Shop[ShopListing]
    Trade[Trade]
    TradeItem[TradeItem]
    Message[Message]
    Comment[MessageComment]
    Role[Role]
    Cap[RoleCapability]
    Log[AdminSubmissionLog]
    Onboard["Onboarding prompt #91;009#93;"]
    Roller["Claimable roller #91;010#93;"]
    Equip["Item usage + equipment #91;011#93;"]
    Freeze["Inactivity freeze #91;018#93;"]

    Visitor -->|register, optional referral code| User
    User -.->|email verified, grants welcome package| Referral
    Referral -->|1 recruit to 1 referral| User
    User -->|welcome package items| Item

    User -->|no herd leader yet| Onboard
    Onboard -.-> Roller
    Roller -.->|claim a rolled design| Horse

    User -->|owns 0..n| Herd
    Herd -->|holds 0..n, one is herd_leader_id| Horse
    User -->|owns 0..n directly| Horse

    Horse -->|submit design, state=pending| Log
    Log -->|staff publish, state=public| Horse
    Horse -->|on publish, auto-creates slots| Slot

    Slot -->|offer / accept / decline| Transfer
    Transfer -->|reassigns holder_id| Slot
    Slot -->|reserved as sire_slot + dam_slot| Req
    Req -->|staff roll, results_ready| Message
    Req -->|requester picks a genotype| Horse

    Item -->|1 item to 0..1 listing| Shop
    Shop -->|purchase| User
    User -->|user_items pivot, n to n with quantity| Item
    Item -.-> Equip
    Equip -.->|consume / attach to horse| Horse

    User -->|offer trade| Trade
    Trade -->|1 trade to n lines| TradeItem
    TradeItem -->|references| Item
    Trade -->|accept, moves items between users| User

    Message -->|1 thread to n comments| Comment
    User --> Comment

    User -->|single role column| Role
    Role -->|n capability rows, DB-overridable| Cap
    Cap -->|gates the 7 admin areas| Log

    User -.-> Freeze

    class Onboard,Roller,Equip,Freeze gap
```

**Not drawn** (site furniture, no gameplay edges): `CmsPage`, `MenuItem`, `Announcement`. Lifecycle models (`LifecycleSetting`, `LifecycleRunLog`, `NpcDeathProposal`) appear in §3.

**Economy in one paragraph.** Items live in a `user_items` pivot carrying quantity, so nothing is a distinct row per copy. `ShopListing` puts an item up for purchase. `Trade` is a pending offer between two users with `TradeItem` lines, and accepting moves quantities. Vouchers are items redeemed through a choice pool for another item. Welcome packages and referral rewards are the two automatic item sources. Items currently have no gameplay effect, which is `[011]`.

---

## 2. Breeding

Two horses, two slots, one staff roll, one foal. The slot economy is the constraint: a public horse gets breeding slots on publish, slots are transferable between players, and a completed breeding consumes them.

```mermaid
stateDiagram-v2
    direction TB

    state "BreedingSlot" as SlotBlock {
        [*] --> Available: horse published, ensureSlotsForHorse
        Available --> Reserved: attached to a request
        Reserved --> Available: request cancelled or rejected
        Reserved --> Consumed: foal created
        Consumed --> [*]
    }

    state "BreedingSlotTransfer" as TransferBlock {
        [*] --> Pending: holder offers slot
        Pending --> Accepted: recipient accepts, holder_id moves
        Pending --> Declined: recipient declines
        Pending --> Cancelled: sender withdraws
        [*] --> Granted: staff grants a Sanctuary-held slot
    }

    state "BreedingRequest" as ReqBlock {
        [*] --> PendingStaff: submitRequest, reserves sire and dam slots
        PendingStaff --> Cancelled: requester cancels
        PendingStaff --> Rejected: staff rejects, or eligibility fails at roll
        PendingStaff --> ResultsReady: staff rolls genetics, posts a Message
        ResultsReady --> Completed: requester picks an option, foal created
    }
```

**Eligibility, enforced at submit and again at roll:** both horses public, both alive, both have a sex, both at least 2 years old, sire is a stallion, dam is a mare, not the same horse, both genotypes parseable. A genetics provider failure leaves the request `pending_staff` for retry rather than failing it.

**Transfers only apply to `Available` slots.** A slot with a pending offer cannot be offered again. Staff can grant slots held by the Sanctuary user, which is how ownerless NPC horses' slots reach players.

Roadmap gaps in this subsystem:

| Item | Status | Effect on this diagram |
| --- | --- | --- |
| `[023]` Advanced breeding lifecycle and stone modifiers | freezer | Adds modifier inputs to the roll step |
| `[024]` Shared genotype-to-phenotype reader | freezer | Replaces duplicated geno parsing on both sides of the roll |

---

## 3. Lifecycle

One scheduled command, `horses:lifecycle`, running daily. It ages living horses and rolls for death, but **it never kills anything directly**. It files an `NpcDeathProposal` that staff must confirm.

```mermaid
flowchart TD
    classDef gap stroke-dasharray: 6 4,stroke-width:2px

    Cron[["Scheduler: horses:lifecycle daily"]]
    Manual[["Staff manual run, admin lifecycle page"]]
    Due{"isDue per LifecycleSetting cadence?"}
    Skip["Skip, log reason"]
    Age["Age each alive horse by monthsToAgePerCycle"]
    Chance{"deathChancePercent for new age?"}
    NoRoll["No proposal"]
    Roll["Roll against chance"]
    Existing{"Pending proposal already open?"}
    Prop["Create NpcDeathProposal, status=pending"]
    RunLog[("LifecycleRunLog: one row per run")]
    Review{"Staff review"}
    Confirmed["Confirmed: horse marked dead"]
    Rejected["Rejected: horse survives"]
    Voided["Voided: owner changed, proposal invalidated"]
    Health["Health roll application #91;022#93;"]

    Cron --> Due
    Manual -->|force flag can bypass cadence| Due
    Due -->|no| Skip
    Due -->|yes| Age
    Age --> Chance
    Chance -->|none for this age band| NoRoll
    Chance -->|percent| Roll
    Roll -->|survives| NoRoll
    Roll -->|dies| Existing
    Existing -->|yes| NoRoll
    Existing -->|no| Prop
    Prop --> Review
    Review --> Confirmed
    Review --> Rejected
    Prop -.->|owner reassignment voids pending proposals| Voided
    Age --> RunLog
    Prop --> RunLog
    Age -.-> Health

    class Health gap
```

**`--dry-run`** computes the whole run and reports proposals without writing them. **`--force`** ignores the cadence check. Both are how you exercise this safely.

`LifecycleSetting` is a single settings record holding the cadence, months aged per cycle, and the age-band-to-death-chance table. It is editable by staff with the `lifecycle` capability.

Roadmap gap: `[022]` applies a health roll on top of aging, which currently does nothing.

---

## 4. Moderation and permissions

Every player-authored horse enters as `state=pending` and needs staff to publish it. Permissions are a role plus a capability set, where the set has DB-backed overrides on top of hardcoded defaults.

```mermaid
flowchart TD
    classDef gap stroke-dasharray: 6 4,stroke-width:2px

    subgraph Perms["Permission resolution"]
        RoleCol["User.role: admin, designer, story_admin, game_master, user"]
        Defaults["Hardcoded default capabilities per role"]
        DB[("role_capabilities table")]
        Effective["Effective capabilities"]
        Areas["submissions · rollers · lifecycle · users · items · shop · cms"]
        RoleCol --> Defaults --> Effective
        DB -->|overrides defaults when rows exist| Effective
        Effective --> Areas
    end

    subgraph Queue["Submission queue"]
        New["New horse: state=pending, public_horse_id null"]
        Edit["Edit of a public horse: state=pending, public_horse_id set"]
        Q{{"Admin queue"}}
        Contacted["Contacted: staff requests changes"]
        Archived["Archived: hidden from the active queue"]
        Published["Published: state=public, approved_at set, slots created"]
        Approved["Approved edit: merged onto the public horse"]
        Prio["Designer high-priority flag #91;029#93;"]
        Npc["Designer NPC design flag #91;030#93;"]
        Terms["Upload terms + graveyard disposition #91;008#93;"]

        Terms -.->|gate on create and upload| New
        New --> Q
        Edit --> Q
        Q --> Contacted
        Q --> Archived
        Q --> Published
        Q --> Approved
        Archived -->|unarchive| Q
        Contacted -->|owner revises| Q
        Q -.-> Prio
        Q -.-> Npc
    end

    Areas -->|submissions capability required| Q
    Q --> Log[("AdminSubmissionLog: one row per staff action")]
    Contacted --> Msg[("Message to owner, type=horse_submission")]

    class Prio,Npc,Terms gap
```

**Two different pending shapes, easy to confuse.** A pending horse with `public_horse_id = null` is a brand-new submission and gets *published*. A pending horse with `public_horse_id` set is a pending *edit* of an existing public horse and gets *approved*, merging onto the parent. `activePending` means the pending row that has not yet been approved.

**`AdminAction`** is the audit vocabulary: `contacted`, `approved`, `archived`, `unarchived`. Every one writes an `AdminSubmissionLog` row against the horse.

**Other staff surfaces** behind the same capability system: roller (generate designs), lifecycle (settings and manual runs), users (freeze, ban, role assignment), items, shop listings, CMS pages, announcements.

---

## 5. MVP gap index

Every dashed node above, in one place.

| Item | Status | Subsystem |
| --- | --- | --- |
| `[008]` Design upload terms and graveyard option | next | Moderation |
| `[009]` New-player onboarding flow | next | Overview |
| `[010]` Randomized claimable horses | next | Overview, roller |
| `[011]` Item usage and equipment workflows | next | Economy |
| `[018]` Automatic inactivity freeze | next | Overview, users |
| `[022]` Lifecycle health roll application | next | Lifecycle |
| `[029]` Designer high-priority submission flag | next | Moderation |
| `[014]` Client spec gathering | next | Blocks `[015]` and `[016]` |
| `[031]` Repo-wide lint and format compliance | next | No diagram surface |
| `[015]` `[016]` `[017]` `[019]` `[020]` `[023]` `[024]` `[030]` | freezer | Post-MVP |
