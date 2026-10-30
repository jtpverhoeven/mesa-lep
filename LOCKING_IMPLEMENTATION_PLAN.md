# Locking Implementation Plan (Keyrings + Project Editability)

Audience: an implementing agent. This document records (1) what legacy does, (2) what the Laravel port currently has, (3) the gaps/drift, and (4) a centralized target design with an ordered work plan. All design decisions are settled (§0.1); implement as written.

---

## 0. TL;DR

- **The legacy keyring system (soft "in use by X" locks) has NOT been ported at all.** No `keyrings` table, model, action, route, event, channel or front-end code exists in `app/`, `database/`, `routes/` or `resources/js/`.
- What *has* been ported is the **project immutability check** (`projects.auth_status` / `projects.locked`), but it is **diffuse**: ~11 hand-written write guards + 3 `read_only` computations + 2 front-end re-computations, with inconsistent HTTP responses (422 vs 403) and at least one missing guard.
- That ported check deviates from legacy (see §2.3): legacy `projects.locked` only blocks *authorization*, and legacy only treats `auth_status == 1` as authorized. **The Laravel behaviour is kept on purpose** (§0.1).
- Reverb/Echo is already wired (private `samples.{id}` channel, `resources/js/echo.js`), so realtime keyrings can reuse that plumbing.
- Plan: build one `Keyring` domain (DB row = holder, Reverb presence = liveness, admin steal, broadcast changes) and one central **editability gate** used by every read payload (`read_only`) and every write action.

### 0.1 Settled decisions

| # | topic | decision |
|---|---|---|
| 1 | `projects.locked` | Keep current Laravel behaviour: `locked != 0` makes the **whole project read-only** until unlocked (not just blocking authorization as in legacy). |
| 2 | `auth_status` | Keep current Laravel behaviour: **any non-zero** `auth_status` (incl. `2`, "Autorisatie in behandeling") is read-only. |
| 3 | Keyring enforcement | **Server-side**: every write action rejects requests when another user holds the keyring (legacy was UI-only). |
| 4 | Liveness | **Reverb presence** + long `expires` fallback (default `config('session.lifetime')`), no heartbeat/polling. Takeover rule in §3.1.4. |
| 5 | Not-editable response | HTTP **423 Locked**, one JSON shape (§3.4). |
| 6 | Code placement | Enum and other non-action domain classes in `app/Keyrings/` (same pattern as `app/ChangeTracking`, `app/Confirmations`); actions in `app/Actions/Keyrings/`; model `app/Models/Keyring.php`; event `app/Events/KeyringChanged.php`. |
| 7 | Takeover | Admin **steal** is in scope, permission `keyrings.steal` (§3.6). |

---

## 1. Legacy behaviour (source of truth for parity)

### 1.1 Table `keyrings` (`legacy/app/sql_legacy.sql:423`)

| column | type | meaning |
|---|---|---|
| `id` | int PK AI | lock id (`lock_id`) |
| `type` | varchar(32) | stores the **int** constant below (quirk: varchar holding `0..3`) |
| `type_id` | int | id of the resource |
| `issued` | int | unix ts |
| `issued_to` | int | user id |
| `expires` | int | unix ts = `issued + ALPC_SESSION_LIFETIME` |

No unique index → acquire is racy (check-then-insert).

### 1.2 Types (`keyringsController` constants)

| const | value | resource id | used by |
|---|---|---|---|
| `SAMPLE` | 0 | sample id | sample lookup, project search sample panel, results dialog, confirmation viewer/dialog |
| `PROJECT` | 1 | project id | project search page |
| `EXPORT` | 2 | **project id** | export project, temp project, extern export (extern uses the *project* id under EXPORT) |
| `EXTERN` | 3 | – | defined but unused (call commented out, `exportsController.php:4578`) |

### 1.3 Controller semantics (`legacy/app/controllers/keyringsController.php`)

- `requestLockAndStatus($type, $id)`: `_sweep()` (delete all expired) → `_checkLocked` → if nobody holds it, insert one. Returns:
  - free/own: `{locked: false, lock_placed: true, lock_id}`
  - held by other: `{locked: true, lock_placed: false, lock_id, locked_by_id, locked_by_name, locked_by_avatar}`
- Ownership is **per user**, not per tab/session: the same user in two tabs is "owner" in both.
- Re-requesting an own lock does **not** extend `expires` (lock dies after session lifetime even if still in use).
- `removeOwnLock($type, $id)`, `removeOwnLockById($id)`: delete only if `issued_to == current user`.
- `removeLockForUser($user)`: defined, **never called** (intended for logout).
- No forced takeover / admin break.

### 1.4 Call sites

| legacy location | type | effect when held by other |
|---|---|---|
| `projectsController::getProjectInformation` (`:1210-1229`) — releases `$_POST['previous_project']` first | PROJECT | UI hides auth/deauth/remove/notes/edit buttons, shows "in use by" (`projectLockCall`, plays `scanLocked.wav`) |
| `samplesController::doLookup` (`:3677-3769`) — releases `previous_sample` first | SAMPLE | UI hides result/edit buttons (`sampleLockCall`) |
| `samplesController::sampleInProjectOverview` (`:4429-4434`) — releases `previous_sample` | SAMPLE | sample form rendered `disabled` |
| `resultsController` (`:659`, `:745`) | SAMPLE | result inputs disabled |
| `confirmationsController::confirmationViewer` (`:386`, `:413`), `renderDialog` (`:1201`, `:1209`) | SAMPLE | confirmation inputs disabled |
| `exportsController::tempProject` (`:851`), project export (`:1085`), extern (`:4579`) | EXPORT | export UI hidden, lock notice shown |

Release on leave:
- `views/projects/search.php:1473` and `views/samples/lookup.php:876`: `beforeunload` → `navigator.sendBeacon('/keyrings/removeOwnLock/{TYPE}/{id}')`.
- `views/exports/{project,tempProject,extern}.php`: `beforeunload` → `sendBeacon('/keyrings/removeOwnLockById/{lock_id}')`.
- `search.php:2167-2175`: explicit AJAX release when closing a project.

**Enforcement is UI-only.** No legacy write endpoint rejects a request because of a keyring; `locked` only drives `disabled`/hidden controls.

### 1.5 Legacy *edit* lock = `auth_status == 1` OR keyring held by other

- `samplesController::partOfAuthProject` (`:4336`) and `projectsController::authStatus` (`:137`) both check **`auth_status == 1`** only.
- `projects.locked` / `locked_by` / `lock_message` is the **"authorization block"** (`projectsController::lockProject/unlockProject`, `:16-58`, change-tracker events 22/23). It is checked only in `authoriseProject` (`:341`: authorize only if `auth_status == 0 && locked == 0`) and in the UI to hide auth buttons (`search.php:1747`, `:1817`).

---

## 2. Current Laravel state

### 2.1 Keyrings: nothing ported

Verified absent: `Keyring` model, `keyrings` migration, any route/controller/action, broadcast event, channel, front-end beacon/heartbeat. `ImportDatabaseCommand` does not import `keyrings` (correct: it is transient data).

### 2.2 Unrelated "locks" that already exist (leave as-is, out of scope)

These are concurrency mutexes, not user-facing locks. Do not merge them into the keyring system:
- DB row locks: `lockForUpdate()` in many actions (e.g. `app/Actions/SampleBuffers/*`, `AuthorizeProject`, `UpdateResultValue`).
- Cache mutexes: `Cache::lock` in `app/Jobs/SendLabelPrinterSpoolJob.php:48`, `app/Http/Controllers/ManualLabelPrintingController.php:46`, `app/Actions/LabelDesigns/SaveLabelDesign.php:16`, `app/Actions/AssuranceForms/GetOrCreateAssuranceForm.php:21`.
- Front-end "lock" toggles that mean "remember value for next entry": `projectStore.lockedFields` (`SampleProjectEditor.vue`), `sampleResearchStore.locked` (`SampleCreateForm.vue`). Unrelated.
- `confirmations.in_use` column: legacy JSON of active support media. Unrelated.

Naming convention: in new code use **"keyring"** for user presence locks and **"authorization block"** for `projects.locked`, so neither collides with the mutexes above.

### 2.3 Project editability check: diffuse, inconsistent, and drifted

Write guards (each hand-rolled, all `locked || auth_status`):

| file | response |
|---|---|
| `app/Actions/Results/UpdateResultValue.php:36` | `ValidationException` (422) |
| `app/Actions/Confirmations/ConfirmationMutation.php:35` | in-action check |
| `app/Actions/SampleAnalyses/UpdateRoamingAnalysis.php:28` | in-action check |
| `app/Actions/Samples/UpdateLookupResearch.php:31` | in-action check |
| `app/Actions/Samples/UpdateSampleInoculationConditions.php:34` | in-action check |
| `app/Actions/Samples/RegisterSampleInoculation.php:37` | in-action check |
| `app/Actions/Results/ChangeVetoResult.php:41` | `abort_if(..., 403)` |
| `app/Actions/Projects/DeleteProjectSearchSample.php:28` | `abort_if(..., 403)` |
| `app/Actions/Projects/UpdateProjectSearchSample.php:25` | `abort_if(..., 403)` |
| `app/Http/Controllers/SampleMetadataController.php:64` | `abort_if` **in controller**, query-based (`!= 0`) |
| `app/Actions/Projects/ResolveSampleRegistrationProject.php:22` | refuses to reuse such a project |

Read flags (`read_only`):
- `app/Actions/Projects/GetProjectSearchSample.php:61`
- `app/Confirmations/ConfirmationState.php:27` and `:97`

Front-end re-computations (duplicate the server rule):
- `resources/js/stores/sampleLookupStore.js:10` (`project.locked || project.auth_status || detailsData.read_only`)
- `resources/js/stores/projectSearchStore.js:28`
- UI copy: `SampleLookup.vue:151` "Project is vergrendeld of geautoriseerd."

Problems:
1. **Missing guard**: `app/Actions/ProductGroups/UpdateSampleProductGroup.php` (route `samples.lookup.product-group.update`) has no editability check (the UI hides it, the server allows it). Add it via the central gate.
2. **Inconsistent errors**: 422 vs 403 for the same condition; the front-end (`sampleLookupStore.js:150`) maps 403 to read-only, so 422 cases behave differently. Unify on 423 (§3.4).
3. **Intentional deviation from legacy** (decided, keep it; §0.1 items 1–2):
   - `projects.locked != 0` → whole project read-only (legacy: only blocks authorization).
   - Any non-zero `auth_status` (incl. `2` = "Autorisatie in behandeling", `ProjectInfo.vue:67`) → read-only (legacy: only `== 1`).
   The central gate must encode exactly this rule: `read_only = locked != 0 || auth_status != 0`.
4. **Keyring dimension absent**: no read payload or write action considers "in use by another user".
5. **Not ported**: the authorization block itself (`lockProject` / `unlockProject`; legacy UI `#lockProject`/`#unlockProject`). `AuthorizeProject.php:45` correctly *honours* `locked`, but there is no route/action to set or clear it.

### 2.4 Realtime infrastructure available

- Reverb in `compose.yaml` (`reverb` service), `config/reverb.php`, `.env.example` (`BROADCAST_CONNECTION=reverb`; note `config/broadcasting.php:18` defaults to `null` when env is missing).
- `bootstrap/app.php:13` registers `routes/channels.php`; only channel: `samples.{sampleId}`.
- Events (all `ShouldBroadcastNow`, `PrivateChannel("samples.{id}")`): `ConfirmationUpdated`, `ConfirmationDecisionRequired`, `AnalysisResultCalculated`, `AnalysisResultCalculationFailed`.
- `resources/js/echo.js` singleton `getEcho()`; subscribers in `SampleLookup.vue:72-143` and `ProjectSearch.vue:18-49`; `confirmationStore.js` sends `X-Socket-ID` so `->toOthers()` works.
- Scheduler: `routes/console.php` has no schedules yet; verify a scheduler process (`schedule:work`) exists in Sail/production before relying on scheduled sweeps.

---

## 3. Target design

Two central concerns, one gate:

```
                ┌───────────────────────────────┐
 read payloads ─►  GetEditability (read_only,    │◄─ write actions call
 (lookup,        │  reasons[], keyring status)   │   EnsureEditable (throws)
  project search,└──────┬──────────────┬─────────┘
  confirmation,         │              │
  reports)       Project rules     Keyring rules
                 (auth_status,     (keyrings table,
                  auth block)       presence + broadcast)
```

### 3.1 Keyring backend

Follow existing conventions: actions in `app/Actions/Keyrings/` with `handle()`, non-action domain classes (enum, exception, presence client) in `app/Keyrings/`, model in `app/Models`, event in `app/Events`, legacy column names preserved.

Target file layout:

```
app/Keyrings/KeyringType.php                    enum
app/Keyrings/KeyringPresence.php                Reverb presence query (wraps Pusher HTTP API, short timeout)
app/Keyrings/ResourceNotEditableException.php   renders 423
app/Models/Keyring.php                          (+ database/factories/KeyringFactory.php)
app/Actions/Keyrings/GetKeyringStatus.php
app/Actions/Keyrings/AcquireKeyring.php
app/Actions/Keyrings/StealKeyring.php
app/Actions/Keyrings/ReleaseKeyring.php
app/Actions/Keyrings/ReleaseKeyringById.php
app/Actions/Keyrings/ReleaseUserKeyrings.php
app/Actions/Keyrings/SweepExpiredKeyrings.php
app/Actions/Projects/GetEditability.php
app/Actions/Projects/EnsureEditable.php
app/Events/KeyringChanged.php
app/Http/Controllers/KeyringController.php
config/keyrings.php
```

1. **Migration** `create_keyrings_table` (use `vendor/bin/sail artisan make:migration --no-interaction`): legacy columns verbatim (`id`, `type` varchar(32), `type_id` int, `issued` int, `issued_to` int, `expires` int) **plus** `unique(['type', 'type_id'])` and `index('issued_to')`. The unique index fixes the legacy race.
2. **Enum** `App\Keyrings\KeyringType: int` with `Sample = 0`, `Project = 1`, `Export = 2`, `Extern = 3` (TitleCase keys). Add a helper mapping URL slugs (`sample|project|export`) to cases. `app/Keyrings/` is approved.
3. **Model** `App\Models\Keyring`: `$timestamps = false`; casts `type` → `KeyringType` (stored as string-of-int in varchar; make sure the cast round-trips `'0'`); `holder(): BelongsTo` (`issued_to` → `User`); scopes `active()` (`expires >= now()->timestamp`) and `forResource(KeyringType, int)`.
4. **Liveness = WebSocket presence, not HTTP heartbeats** (see §3.3). The DB row says *who* holds the keyring; the holder's membership in the Reverb presence channel says whether they are *still there*. `expires` stays as a long backstop only (legacy `ALPC_SESSION_LIFETIME`, e.g. `config('session.lifetime')`), for when Reverb is down. Put tunables in `config/keyrings.php`.

   **Takeover rule (single source of truth, used by acquire and sweep):**

   | Reverb presence query | holder in channel | `expires` | result |
   |---|---|---|---|
   | answered | yes | any | **keep** holder; server pushes `expires` forward (holder is demonstrably active) |
   | answered | no | any | **take over** immediately (`reason: abandoned`) |
   | failed / timed out | unknown | in the future | **keep** holder (no takeover on uncertainty *before* the deadline) |
   | failed / timed out | unknown | passed | **take over** (`reason: expired`) — legacy parity: `expires` is a hard deadline |

   So when Reverb is down, `expires` *does* permit takeover even though liveness is unknown. This is deliberate: it is exactly the legacy behaviour, and it is safe because writes are enforced server-side (§3.4). A holder who is in fact still working gets a clear "taken over" rejection on the next save instead of silently overwriting. Before the deadline, only an admin steal (§3.6) can resolve it.

   `expires` is refreshed without any polling: on the holder's own acquire (navigation, socket reconnect), on every successful write that passes `EnsureEditable`, and when the server confirms presence (row 1 above).
5. **Actions** (`app/Actions/Keyrings/`):
   - `GetKeyringStatus(type, id, viewer)` — side-effect free; returns the status shape below. Used inside read payloads.
   - `AcquireKeyring(type, id, user)` — in a transaction with `lockForUpdate` on the row: if free → take it; if own → refresh `expires`; if held by another user → ask Reverb whether the holder is still a member of the presence channel (`GET /apps/{app}/channels/presence-keyrings.{type}.{id}/users` via `Broadcast::connection('reverb')->getPusher()`, short timeout) and apply the **takeover rule** table in §3.1.4. On unique violation re-read. Broadcast `KeyringChanged` only when the holder changed. Returns status (legacy `requestLockAndStatus`).
   - `StealKeyring(type, id, actor)` — see §3.6.
   - `ReleaseKeyring(type, id, user)` and `ReleaseKeyringById(id, user)` — only own rows (legacy `removeOwnLock*`); broadcast release.
   - `ReleaseUserKeyrings(user)` — legacy `removeLockForUser`; wire to `Illuminate\Auth\Events\Logout` (and when a user is disabled).
   - `SweepExpiredKeyrings` — backstop only: for rows past `expires`, apply the same takeover rule (if Reverb confirms the holder is present, push `expires` forward instead of deleting); delete the others **one by one and broadcast a release (`reason: expired`) for each** (do not use `MassPrunable`, it skips events). Expose as an artisan command (`keyrings:sweep`) and schedule hourly in `routes/console.php`; the expiry check for the requested resource is also done lazily inside `AcquireKeyring` (legacy `_sweep`), so correctness does not depend on the scheduler running.
6. **Status shape** (keep legacy keys so ports of legacy views map 1:1, add `expires_at`):
   ```json
   { "locked": false, "lock_placed": true, "lock_id": 12,
     "locked_by_id": null, "locked_by_name": null, "locked_by_avatar": null,
     "expires_at": "ISO-8601" }
   ```
   `locked` is viewer-relative (true only when held by *another* user). Broadcast payloads must therefore carry the **holder** (`holder_id`, name, avatar URL, `lock_id`, `expires_at`, or `null` when released) and let the client compute `locked = holder_id !== currentUserId`.
7. **Avatar**: there is currently no route to show an arbitrary user's avatar (only own images `routes/web.php:72` and the project-authorizer avatar `projects.info.avatar`). Add a generic, authorized user-avatar route (or reuse `GetProjectInfo.php:41-55`'s approach) for `locked_by_avatar`.

### 3.2 HTTP endpoints

One controller (e.g. `KeyringController`) under `auth` middleware, typed by `{type}` constrained to `sample|project|export`, with the same view permission as the resource (`samples.view` / `projects.view`; export also `reports.generate`):

| method | path | action |
|---|---|---|
| POST | `/keyrings/{type}/{typeId}` | acquire / status (also "claim after holder left") |
| POST | `/keyrings/{type}/{typeId}/steal` | steal (permission-gated, §3.6) |
| DELETE | `/keyrings/{type}/{typeId}` | release own |
| POST | `/keyrings/{keyring}/release` | beacon-friendly release (own only) |

There is no heartbeat endpoint. Explicit release is still used when switching resources inside the SPA. On tab close it is only a fast path: closing the socket already sends `member_removed`. Beacon notes: `navigator.sendBeacon` can only POST and cannot set headers, so include `_token` in a `FormData` body; preferred is `fetch(url, { method: 'DELETE', keepalive: true, headers: { 'X-CSRF-TOKEN': … } })` on `pagehide`, with `sendBeacon` as fallback. Use `pagehide`, not `beforeunload` (unreliable on mobile/bfcache).

Legacy acquires as a side effect of the data GET (`doLookup`, `getProjectInformation`). In Laravel, keep GETs side-effect free: include `GetKeyringStatus` in the payload for first paint and acquire via the POST endpoint from the client store. Server-side write enforcement (§3.4) makes the small window safe.

### 3.3 Realtime

Verified against installed `laravel/reverb` v1.11.1:
- Presence channels emit `member_added` / `member_removed`; `member_removed` fires only when the **last** connection of that `user_id` leaves (`InteractsWithPresenceChannels.php`). That matches legacy per-user ownership, so closing one of two tabs does not release the keyring.
- Reverb pings idle connections and removes stale ones every 60 s (`StartServer.php:97`, `activity_timeout` in `config/reverb.php`). A crashed or sleeping client therefore drops out within about 1–2 minutes with **no app-level traffic**, because the WebSocket protocol already does the ping/pong.
- Reverb has **no webhooks**; PHP is not told when a member leaves. Server-side "is the holder still here?" questions are answered with the Pusher HTTP API (`ChannelUsersController`), which `pusher/pusher-php-server` (already installed) can call.

Design:
- **Presence channel** `keyrings.{type}.{typeId}` (`Broadcast::channel` returning user info `['id', 'name', 'avatar_url']`), authorized like the resource (mirror `samples.{sampleId}`: `enabled` + permission + resource exists). Everybody viewing the resource joins, holder and viewers alike. Bonus: the UI gets "also viewing" for free.
- Event `App\Events\KeyringChanged implements ShouldBroadcastNow`, `broadcastAs('keyring.changed')`, on the same presence channel, payload = holder-centric shape (§3.1.6) plus `reason: acquired|released|abandoned|stolen|expired` and `previous_holder_id`.
- Fire on acquire (holder changed), release, abandoned takeover, steal, sweep-expiry and logout release.
- **Handover flow**: the holder disconnects → Reverb sends `member_removed(holder_id)` to the remaining viewers → their `keyringStore` calls `POST /keyrings/{type}/{id}` (add a small random delay, 0–500 ms, to spread the burst) → the server checks presence and the row lock, and exactly one viewer wins → `KeyringChanged` tells everyone. If no one else is viewing, the row stays until the next viewer's acquire finds the holder absent and takes over, or until the backstop sweep runs. If Reverb is down, there are no `member_removed` events and the presence query fails, so the takeover rule falls back to `expires` (§3.1.4): locked until the deadline, free after it, admin steal in between.
- **Reconnect**: when the client socket reconnects (Echo `connected` state), re-join and call acquire again. If the keyring was taken meanwhile, show the "taken over" state.

### 3.4 Central editability gate

Replace every guard in §2.3 with one implementation.

Project rule (decided, §0.1): `read_only` when `projects.locked != 0` **or** `projects.auth_status != 0`. Keyring rule: `read_only` when the relevant keyring is held by another user (after applying the takeover rule). Which keyring applies to a write: sample-scoped writes (results, confirmations, research, references, metadata, product group, sample details, veto) → `Sample` keyring of that sample; project-scoped writes (project details, remove sample from project, notes) → `Project` keyring; report generation → `Export` keyring of the project. Writes from pages that do not take keyrings (e.g. sample register/inoculation, sample registration) only apply the project rule.

Place both actions in `app/Actions/Projects/` (they are about project/resource editability and consume the keyring actions).

- `GetEditability` (read): input = project (+ optional `KeyringType`/id + viewer). Output:
  ```json
  { "read_only": true, "reasons": ["authorized" | "authorization_blocked" | "keyring"], "keyring": { …status… } }
  ```
- `EnsureEditable` (write): same inputs, throws one domain exception (e.g. `ResourceNotEditableException`) carrying the reason and keyring status; must be called **inside** the action's transaction after `Project::query()->lockForUpdate()` (keep the current row-locking pattern).
- `App\Keyrings\ResourceNotEditableException` renders `423 Locked` with `{ message, reason, keyring }` (messages in Dutch, e.g. reuse "Dit project is vergrendeld of geautoriseerd." for the project reasons and "Dit monster is in gebruik door {naam}." for the keyring reason). Update tests that currently assert 403/422 for this condition (e.g. `tests/Feature/VetoResultTest.php`) and the front-end error mapping (`sampleLookupStore.js:150`, plus any store that maps 403/422 to read-only) so a 423 switches the UI to read-only and refreshes keyring status. Permission failures stay 403; only the "not editable" condition becomes 423.
- On success, `EnsureEditable` refreshes the holder's `expires` (§3.1.4).
- Add a `Project` model helper (e.g. `isAuthorized()` / `isAuthorizationBlocked()`) so the rule exists in one place; queries like `SampleMetadataController.php:64` should call the action instead of re-encoding the rule.
- Front-end: stores consume `read_only` + `reasons` + `keyring` from the server instead of recomputing from `project.locked`/`auth_status`.

### 3.5 Front-end

- New Pinia store `resources/js/stores/keyringStore.js` (stores folder already exists; avoids a new `composables/` folder). Responsibilities:
  - `acquire(type, id)`, `release(type, id)`, `steal(type, id)`, holding multiple keyrings at once (project + sample on project search).
  - `getEcho().join('keyrings.{type}.{id}')`; `.here()` / `.joining()` / `.leaving()` for viewers; on `.leaving(holder)` call acquire (with jitter); `.listen('.keyring.changed')` updates status. When it changes to someone else, switch to read-only right away and show "overgenomen door {naam}" if `reason === 'stolen'`.
  - No timers. Re-acquire after Echo reconnects.
  - Release on resource switch (replaces legacy `previous_sample` / `previous_project` POST fields) and, as a fast path, on `pagehide` (keepalive fetch / beacon).
  - Expose `isLockedByOther(type, id)`, holder info, viewers.
- Shared notice component (e.g. `KeyringNotice.vue`) "In gebruik door {naam}" with avatar, using theme tokens/OpenVue per the shared theme rules. to be placed at the top of the page, similar (but updated) to the legacy implementation. 
- Sound: legacy plays `scanLocked.wav`; `public/sfx/` has no equivalent and `sfx.js` has no `locked` key. Either add the asset + key or use `warning`.

Integration points:

| page / component | type | legacy equivalent |
|---|---|---|
| `SampleLookup.vue` (+ `ConfirmationDialog`, results, references, metadata, product group) | Sample | `doLookup`, results, confirmations |
| `ProjectSearch.vue` project panel | Project | `getProjectInformation` |
| `ProjectSearch.vue` sample panel (`GetProjectSearchSample`) | Sample | `sampleInProjectOverview` |
| `ProjectReportForm.vue` / `reports.index` (normal + temporary) | Export (project id) | `exports/project`, `tempProject` |
| extern export, if/when ported | Export (project id) | `exports/extern` |

Confirmations/results do not acquire separately in the SPA if the parent page already holds the Sample keyring; they just read it from the store (legacy acquired per dialog because pages were separate requests).

### 3.6 Stealing (administrator takeover)

Stealing does not depend on how liveness is detected; it is just another way the holder can change. It does need **server-side enforcement** (§3.4). Without it, the victim's open tab and any requests already in flight would keep writing after the steal.

- Permission: new Spatie permission `keyrings.steal` (Dutch label, e.g. "Vergrendelingen van andere gebruikers overnemen"), added to `app/Support/PermissionCatalog.php` and checked in the route (`can:keyrings.steal`). `DatabaseSeeder` syncs all catalog permissions to the `administrator` role, so administrators get it automatically; other roles get it through the existing role administration. Existing databases need the permission created (rerun the seeder or add a small data migration, following how earlier permissions were introduced).
- `StealKeyring(type, id, actor)`: in a transaction, `lockForUpdate` the row and set `issued_to = actor`, `issued = now`, `expires = now + backstop`. Do a compare-and-swap on the previous `issued_to` so two simultaneous steals cannot both win. If the row is free, behave like acquire.
- Audit: write a change-tracker entry (`ChangeTracker::changed`, new event code) with victim and actor.
- Broadcast `KeyringChanged` with `reason: 'stolen'` and `previous_holder_id`.
- Victim UI: becomes read-only immediately, shows a notice, and leaves unsaved input visible but disabled, so it can be copied rather than silently lost. A write already in flight is rejected by `EnsureEditable` with the "keyring" reason, and the store shows the same notice.
- The victim can only get the keyring back when it is released or abandoned, or by stealing it with the same permission.
- The UI shows "Overnemen" in `KeyringNotice` only for users with the permission, and asks for confirmation first.

---

## 4. Ordered work plan

1. **Centralize project editability (no keyrings yet)**: build `GetEditability`/`EnsureEditable` with the project rule only (`locked != 0 || auth_status != 0`, unchanged behaviour); replace all guards in §2.3, add the missing product-group guard, introduce `ResourceNotEditableException` (423), switch front-end stores to server `read_only`. Run existing feature tests (`VetoResultTest`, `SampleLookupTest`, `ProjectOverviewTest`, confirmation tests, etc.) and update their expected status to 423 where they assert the project-locked case.
2. **Keyring backend**: migration, enum, model (+ factory), actions (incl. `StealKeyring` + permission), routes/controller, logout listener, backstop sweep command + schedule, avatar route. Feature tests: acquire free / own / other-present / other-absent (fake the Reverb users query), Reverb unavailable → no takeover before `expires` and takeover after it, Reverb confirms holder present → `expires` pushed forward and no takeover even past `expires`, successful write refreshes `expires`, release own only (cannot release others), beacon route with `_token`, logout release, unique-index race, steal with and without permission, concurrent steal compare-and-swap, sweep broadcasts.
3. **Realtime**: `KeyringChanged` event + `keyrings.{type}.{typeId}` presence channel auth (returns user info); tests with `Event::fake()` / channel authorization tests.
4. **Gate integration**: add the keyring dimension to `GetEditability`/`EnsureEditable`; include `keyring` in `LookupSample`, `GetProjectSearchDetails`, `GetProjectSearchSample`, `ConfirmationState`, report index payloads. Tests: write rejected with 423 when another user holds the keyring; allowed for holder and after takeover rule frees it; write after being stolen is rejected.
5. **Front-end**: `keyringStore.js`, notice component (incl. steal button), wire into pages in §3.5; `vendor/bin/sail npm run build`; manual two-browser check (lock shown, handover on tab close, handover after network kill within ~2 min, steal makes victim read-only instantly).
6. **Authorization block set/clear** (not yet ported): port `lockProject`/`unlockProject` (sets `locked`, `locked_by`, `lock_message`; change-tracker events 22/23) as actions in `app/Actions/Projects/`. Since `locked` makes the whole project read-only (§0.1), these actions must themselves bypass the project rule, and should broadcast so open pages refresh `read_only` (use a separate `ProjectEditabilityChanged` event on the project's keyring presence channel, not `KeyringChanged`). Skip if already tracked as a separate task.

Run `vendor/bin/sail bin pint --dirty --format agent` after PHP changes.
