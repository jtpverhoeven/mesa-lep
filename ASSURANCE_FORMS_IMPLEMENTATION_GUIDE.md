# Borgingsformulier Laravel Transplant Guide

## Directive for the implementing agent

Implement the legacy `assuranceForms` feature in Laravel 13 with minimum churn. Preserve its observable behavior, Dutch terminology (although in code and routes use english), legacy IDs, day-based ownership, date strings, completion rules, confirmation coupling, and historical data. Refactoring proprietary framework mechanics into Eloquent relationships, actions, requests, resources, and Vue is expected.

Do not redesign the laboratory workflow. In particular:

- A borgingsformulier belongs to an **inoculation day**, not to a project, assay, confirmation, or calendar day on which confirmation work happens.
- A THT/material value belongs to that daily form and is shared by every sample and confirmation using that medium on that inoculation day.
- Confirmation metadata continues to own `inzet`, `aflees`, and control values. It must not become the source of truth for THT.
- Keep `samples.sample_innoculated`, `sampleanalysis`, `media.hasDate`, `media.type`, `media.supplements`, `media.acceptable_range`, `confirmations.in_use`, and the existing confirmation JSON conventions unchanged.
- Preserve imported legacy form IDs. They are referenced by legacy assurance revision rows.
- Do not silently repair, merge, discard, or reinterpret malformed legacy data.
- Do not add dependencies, broad refactors, or unrelated schema cleanup.
- Do not add or change automated tests in this implementation pass. Use the manual verification checklist in this document.
- Do not run Git commands. Run PHP, Artisan, Composer, and Node commands only through `vendor/bin/sail`.
- There is laravel boost available in the sail environment.

## Executive decisions

1. Use a normalized runtime representation rather than continuing to mutate the numbered JSON blob.
2. Keep a compatibility parent table named `assuranceforms`, preserve its legacy `id`, `date`, and `is_complete` values, and retain the original JSON bytes in `legacy_data`.
3. Store editable form values in relational child records keyed by the exact legacy field IDs, such as `b0_ingezet`, `b2_tht_pfz`, `b3_18`, and `b3_extra_18_4`.
4. Store per-sample “used after THT” evidence separately. A marker is keyed by the **parent media ID**, exactly as legacy block 5 was.
5. Store the historical user dropdown snapshot separately so deleted or renamed users do not change an old form’s displayed choices.
6. Make `form_date` the unique canonical day while retaining `date` as the original midnight Unix timestamp string. All day conversion must use the legacy laboratory timezone, `Europe/Amsterdam`; the application currently defaults to UTC, so never derive these boundaries with an implicit timezone.
7. Treat the normalized rows as the only runtime source of truth. `legacy_data` is immutable import evidence and a rollback/audit aid, not a dual-write target.
8. Replace the existing `ConfirmationAssuranceFields` placeholder binding. Do not copy THT into `confirmations.metadata`; legacy rendering and readiness already treated the assurance form as authoritative even though its generic save endpoint also left a duplicate metadata value behind.
9. A shared THT change must recalculate every enabled confirmation on that inoculation day that uses the affected parent medium, then broadcast the existing `ConfirmationUpdated` event for each changed analysis.
10. Import with a dedicated idempotent Artisan command. It must upsert by preserved legacy ID, rebuild only that form’s normalized children, retain raw JSON, and never truncate tables.

## Source of truth map

Read these files before implementation. The legacy controller is authoritative where this guide calls out behavior; current Laravel files define the integration boundary.

| Concern | Source |
| --- | --- |
| Legacy form lifecycle, synchronization, values, explanations, completion | `legacy/app/controllers/assuranceFormsController.php` |
| Legacy form screen behavior | `legacy/app/views/assuranceForms/view.php` |
| Legacy form layout | `legacy/app/templates/assuranceForms/form.php` and sibling templates |
| Legacy table shape | `legacy/app/sql_legacy.sql` (`assuranceforms`) |
| Media set calculation | `legacy/app/controllers/assaysController.php::fetchMediaAssuranceFormStreamlined()` |
| Confirmation rendering and readiness use of THT | `legacy/app/controllers/confirmationsController.php` |
| Confirmation save ordering | `legacy/app/views/samples/lookup.php::processConfirmationFieldSaveQueue()` |
| Confirmation expiry UI and explanation flow | `legacy/app/views/confirmations/confirmationViewer.php` |
| Sample audit-trail use of form data | `legacy/app/controllers/samplesController.php` near `sampleAuditTrail()` |
| Current assurance abstraction | `app/Confirmations/ConfirmationAssuranceFields.php` |
| Current placeholder implementation | `app/Confirmations/PendingConfirmationAssuranceFields.php` |
| Current confirmation evaluation | `app/Confirmations/ConfirmationRacetrackEvaluator.php` |
| Current confirmation state assembly | `app/Confirmations/ConfirmationStateBuilder.php` and `app/Confirmations/ConfirmationState.php` |
| Current rejection of THT writes | `app/Actions/Confirmations/UpdateConfirmationMetadata.php` |
| Current API and UI | `app/Http/Controllers/ConfirmationController.php`, `resources/js/stores/confirmationStore.js`, `resources/js/components/ConfirmationRacetrack.vue`, and `resources/js/components/ConfirmationDialog.vue` |
| Existing permissions | `app/Support/PermissionCatalog.php` |

## Exact legacy persistence contract

The legacy table is:

```text
assuranceforms
  id           int primary key auto increment
  date         varchar(32) not null, indexed
  data         text nullable
  is_complete  int not null default 0, indexed
```

`date` is a Unix timestamp string normalized to local midnight. It is not a formatted date. Application values inside `data` generally use `d-m-Y` strings.

The JSON object is decoded with `JSON_FORCE_OBJECT`. Numeric-looking keys therefore behave as associative keys even when their encoded form varies.

```json
{
  "users_available_at_time": {
    "7": "Analist A",
    "12": "Analist B"
  },
  "0": {
    "beheer": "7",
    "afgewogen": "12",
    "instoof": "09:15",
    "ingezet": "7",
    "gegoten": "12"
  },
  "1": {
    "2": {
      "datum_uitstoof": "15-09-2026",
      "tijd_uitstoof": "09:00",
      "datum_aflezen": "15-09-2026",
      "tijd_aflezen": "13:00",
      "afgelezen_door": "7"
    }
  },
  "2": {
    "tht_pfz": "30-09-2026",
    "tht_pfz_buizen": "30-09-2026",
    "tht_bpw": "30-09-2026"
  },
  "3": {
    "18": "30-09-2026",
    "extra_18_4": "29-09-2026",
    "33": "4,5"
  },
  "4": {
    "b2_tht_pfz": "Goedgekeurd door kwaliteitsbeheer",
    "b3_18": "Gebruikt volgens afwijkingsprocedure",
    "b3_extra_18_4": "Supplement vrijgegeven"
  },
  "5": {
    "wasOutOfDateHere": {
      "18": {
        "420": {
          "sampleId": 420,
          "sampleBarcode": "M12345"
        }
      }
    }
  }
}
```

### Block meanings

| Legacy key | Meaning | Required compatibility |
| --- | --- | --- |
| `users_available_at_time` | `user id => display name` snapshot taken when the form is created | Preserve names and IDs even if current users differ. |
| `0` | General personnel/time fields | New forms contain only `ingezet` and `gegoten`. Old forms can also contain `beheer`, `afgewogen`, and `instoof`; render and require them only when present. |
| `1` | Per-assay-duration stove/read fields | MCP-456 stopped creating these. Preserve imported rows and legacy rendering behavior, but do not create duration rows for new forms. |
| `2` | Fixed enrichment-liquid fields | New forms contain `tht_pfz`, `tht_pfz_buizen`, and `tht_bpw`. Old `legionella_temp`, `tht_fraser`, `tht_bolton`, and `tht_citraat` values must survive import even though some are hidden. |
| `3` | Dynamic media, supplement, and material values | A numeric key is a `media.id`; `extra_{mediaId}_{supplementId}` is a supplement value. Values remain strings. |
| `4` | Explanation by full UI field ID | Keys include the `b2_` or `b3_` prefix. Empty strings are retained as empty explanations. |
| `5.wasOutOfDateHere` | Parent media ID, then sample ID, then sample snapshot | Used to prove that a medium was used after THT for a particular sample. |

## Observable legacy behavior

### Day identity and form lifecycle

- The selected day defaults to today and is shown as `d-m-Y`.
- Previous and next actions move exactly one calendar day.
- A form is looked up by local-midnight timestamp.
- Starting a sample creates the day’s form if absent and synchronizes it.
- Adding or removing an analysis and removing a sample synchronize the affected inoculation day.
- Editing a sample’s inoculation date must synchronize both the old and new days. The legacy implementation accidentally synchronizes `time()` for one side of this operation; do not reproduce that defect.
- Synchronization deactivates a form when no samples remain for that day. Externally this behaves like legacy deletion, but retain its imported data and revisions by setting `active=false` rather than physically deleting it.
- Opening/rendering a form synchronizes its dynamic media set before returning values and missing-field information.
- A missing form is shown as not found and can be explicitly created.

Use half-open local boundaries `[startOfDay, nextStartOfDay)` for sample selection. This expresses the intended legacy `midnight` through `tomorrow - 1` behavior without DST or subsecond gaps.

### Which media belong to a day

For all samples inoculated on the form day, load all `sampleanalysis` rows and their assay revisions in bulk.

The day’s parent media IDs are the unique union of:

1. Every ID in each assay revision’s `assays.media_id` JSON.
2. Confirmation-chain media in `assays.confirmation_script`, but only when that analysis has `conf_requested = 1` and the medium is `true` in at least one `confirmations.in_use[df][rep]` scope.
3. Confirmation-support media in `assays.confirmation_support` under the same enabled/in-use rule.

Then apply the legacy display eligibility:

- Media types `1` and `2` appear in the regular or confirmation-media tables only when `hasDate = 1`.
- Media type `3` appears in the Material table only when `hasDate = 1`.
- `confirmation_media = 1` selects the confirmation-media table; it does not independently put a medium on the day.
- Do not filter by `media.active`; the legacy `fetchAll()` did not.
- For every in-scope parent medium, decode its current `media.supplements` JSON and expose `extra_{mediaId}_{supplementId}` fields.
- A newly in-scope parent or supplement starts with an empty value.
- A field leaving the day becomes inactive and disappears from the form and completeness calculation. Retain the row for history. If it later re-enters scope, clear its old value before making it active, matching legacy delete-then-recreate behavior.
- Remove/deactivate out-of-date evidence when its parent media ID is no longer in the day set.

Do this in a `SynchronizeAssuranceForm` action with eager loading and bulk queries. Do not issue media or assay queries inside sample loops.

### Editing fields

The legacy field ID determines storage:

| Prefix | Runtime destination |
| --- | --- |
| `b0_` | General field with the suffix as key. |
| `b1_` | Duration field. Parse the final underscore-separated token as duration and preserve the preceding suffix as the field name. |
| `b2_` | Fixed enrichment field. |
| `b3_` | Dynamic media/material/supplement field. |

Preserve these input details:

- Date controls use and persist `d-m-Y`.
- On the standalone form only, a non-empty date input shorter than six characters gets `-CURRENT_YEAR` appended, except exact lowercase `nvt`.
- `nvt` matching in backend rules is case-insensitive and trimmed.
- Values are strings. Do not turn empty strings into `null`, parse decimal material values, or normalize commas in storage.
- `b0` user fields are revision-rendered as user names, but their stored value remains the user ID string.
- Every ordinary field change immediately recalculates `is_complete`.

### Expiry and material rules

Use one shared domain service, for example `AssuranceValueAssessment`, from the standalone form, confirmation provider, completion calculation, and audit rendering.

Date rule:

- Parse strictly as `d-m-Y`, set both dates to local midnight, and compare them.
- A form value is out of specification when its date is strictly before the form day.
- A confirmation use is out of date when the confirmation `inzet` date is strictly after the shared THT date.
- Equal dates are valid.
- Empty and `nvt` are not out of specification.
- A malformed non-empty date is not highlighted and does not require an explanation in legacy behavior. Preserve this compatibility quirk; validation must not reject it during this transplant.

Material rule for `media.type = 3`:

- Empty value, empty acceptable range, and `nvt` are not out of specification.
- Replace decimal commas with periods only for comparison.
- A nonnumeric value is out of specification.
- Parse only comparisons matching `(<=|>=|<|>|=) number`, including negative and decimal values.
- If any non-whitespace text remains after removing comparisons, consider the range unsupported and do not mark the value out of specification.
- All parsed comparisons must pass. One failed comparison makes the value out of specification.

### Per-sample used-after-THT evidence

This evidence is not equivalent to “the daily form value is before its own day.” It is generated from confirmation usage:

1. Resolve the analysis’s sample and its inoculation-day form.
2. Compare that confirmation step’s `inzet` date to the shared THT/material expiry value.
3. If `inzet > THT`, upsert `(assurance_form_id, parent_media_id, sample_id)` with a barcode snapshot.
4. Otherwise remove that one marker.
5. When the last sample marker is removed, the medium has no `wasOutOfDateHere` condition.
6. Supplement fields use their parent medium ID for this evidence. The explanation itself remains attached to the exact supplement field key.
7. Changing a dynamic media value to `nvt` removes applicable evidence for that medium. Do this consistently for parent and supplement writes rather than preserving the legacy supplement-key mismatch bug.

The rendered warning text remains:

```text
Gebruikt na THT bij monster(s): {barcode1}, {barcode2}
```

If an imported snapshot has no barcode, render `Monster ID {sampleId}`. Keep a deterministic sample-ID ordering.

### Explanation behavior

- Show the explanation control only when the field is currently out of specification or has used-after-THT evidence.
- Red means out of specification with no non-empty explanation.
- Orange means out of specification with a non-empty explanation.
- Saving an explanation does not change the field value.
- An explanation is required for completion only while its condition is active.
- Keep an explanation after the value returns in range; hide it and exclude it from completeness. This preserves the legacy block-4 history.

### Completeness

Recalculate after every field, explanation, evidence, media-scope, or sample-day change. Match PHP `empty()` semantics where noted:

1. Every general field that exists on this form is required; empty and string `"0"` are missing.
2. If imported duration rows exist, every field present in each active duration row is required; empty and `"0"` are missing.
3. Every block-2 field present is required except `tht_fraser`, `tht_bolton`, and `tht_citraat`.
4. Every active dynamic media/material/supplement field is required using PHP `empty()` semantics. Therefore `"0"` is missing, while `nvt` is complete.
5. A non-empty explanation is required for an active field when its date/material rule fails or it has used-after-THT evidence, unless the value is `nvt`.
6. `is_complete` is true only when the missing list is empty.

Preserve the legacy missing labels:

- `Algemeen: {field}`
- `Dag {duration}: {field}`
- `{block2Field}`
- `Uitleg: {block2Field}`
- `Media: {fieldKeyWithoutB3Prefix}`
- `Uitleg media: {fieldKeyWithoutB3Prefix}`

The “forms with missing data” list contains incomplete forms older than `BORG_TIME_BEFORE_CHECK` days. The legacy default is 10. Retain the cvar lookup and do not restore the unused lower-limit behavior from `BORG_TIME_CHECK_LIMIT`.

### Revision and audit behavior

The legacy change tracker used type `13`, the assurance form ID, current user, Unix timestamp, `from`, `to`, and event `Veld gewijzigd: {label}`.

Labels are:

- `beheer`: `Beheer monsteronderzoek`
- `afgewogen`: `Afgewogen door`
- `ingezet`: `Ingezet door`
- `gegoten`: `Gegoten door`
- `instoof`: `Tijd platen in broedstoof`
- Duration fields: the existing Dutch label followed directly by the duration.
- Block-2 THT fields: existing legacy labels.
- Parent media/material: current `media.name`.
- Supplement: `Supplement: {supplement name} voor media {media name}`.

Legacy `saveExplanation()` and automatic evidence updates did not add type-13 rows. Keep the visible imported history exact. Internal timestamps on normalized records may still change.

The sample audit trail consumes this feature too. It must continue to show personnel, enrichment THT, assay media and supplements, materials with units, confirmation media, red out-of-spec styling, explanation text, and assurance revisions for the sample’s inoculation-day form.

## Target schema

Create a focused migration. Use the project’s cross-database migration conventions and do not edit already-deployed migrations.

### `assuranceforms`

| Column | Purpose |
| --- | --- |
| `id` integer primary key | Preserve the legacy ID exactly. |
| `date` string(32) | Preserve the legacy local-midnight Unix timestamp string. |
| `form_date` date unique | Canonical lookup and concurrency key. |
| `is_complete` integer default `0` | Keep the legacy name/type behavior. |
| `active` boolean default `true` | Allows legacy delete behavior without historical data loss. |
| `users_available_at_time` json nullable | Lossless snapshot fallback; child rows below are used for querying/rendering. |
| `legacy_data` long text nullable | Exact original JSON text from import; never mutate at runtime. |

Index `date`, `is_complete`, and `(active, is_complete, form_date)`. The unique `form_date` constraint makes runtime `upsert` and form creation deterministic.

### `assurance_form_users`

- `assurance_form_id`
- `user_id` integer
- `display_name` string
- unique `(assurance_form_id, user_id)`

Do not require a foreign key to `users`: historical snapshots may reference deleted users.

### `assurance_form_fields`

- `id` big integer primary key
- `assurance_form_id`
- `field_key` string, storing the exact full legacy ID
- `section` small integer (`0`, `1`, `2`, or `3`)
- `kind` string: `user`, `time`, `date`, `material`, or `legacy`
- `value` text nullable; preserve strings exactly
- `media_id` integer nullable; for supplements this is the parent medium
- `supplement_id` string nullable
- `duration` string nullable
- `explanation` text nullable
- `active` boolean default `true`
- unique `(assurance_form_id, field_key)`
- indexes on `(assurance_form_id, section, active)` and `(assurance_form_id, media_id, active)`

Do not enforce media/user foreign keys during the transplant. Old rows can legitimately refer to records no longer present.

### `assurance_form_expired_samples`

- `id` big integer primary key
- `assurance_form_id`
- `media_id` integer containing the parent media ID
- `sample_id` integer
- `sample_barcode` string nullable snapshot
- unique `(assurance_form_id, media_id, sample_id)`

### `assurance_form_revisions`

- `id` big integer primary key
- `legacy_id` integer nullable unique
- `assurance_form_id`
- `user_id` integer nullable
- `occurred_at` timestamp
- `event` text
- `from_value` string nullable
- `to_value` string nullable

Import legacy `changetracker` type-13 rows when supplied. Runtime form edits append equivalent records here.

### Sample relationship

Add nullable indexed `samples.assurance_form_id`. Preserve `samples.sample_innoculated` unchanged. Backfill the relationship by converting the timestamp in `Europe/Amsterdam` and matching `form_date`.

Relationships:

- `AssuranceForm::samples(): HasMany`
- `AssuranceForm::fields(): HasMany`
- `AssuranceForm::availableUsers(): HasMany`
- `AssuranceForm::expiredSamples(): HasMany`
- `AssuranceForm::revisions(): HasMany`
- `Sample::assuranceForm(): BelongsTo`
- Confirmation code resolves the form through `SampleAnalysis::sampleRecord.assuranceForm`.

Use `$timestamps = false` where the schema has no timestamps. Follow the project’s `#[Fillable]` and `casts()` conventions.

## Backend implementation

### Legacy operation replacement map

Every public legacy operation must have an intentional replacement. Do not expose compatibility HTTP endpoints merely to retain old method names; preserve their behavior behind the new controller/actions.

| Legacy operation | Laravel replacement |
| --- | --- |
| `view()` | `AssuranceFormController::index()` with the selected date and incomplete-form list. |
| `fetchByDate()` | `AssuranceForm::query()->whereDate('form_date', ...)->active()->first()`, normally reached through `ResolveAssuranceDay`. |
| `removeForm()` | Internal archive/deactivate operation used when the day has no samples; no public destructive endpoint. |
| `show_array()` | No production replacement; `legacy_data` supplies forensic inspection without a debug route. |
| `scanForFormUpdates()` | `SynchronizeAssuranceForm`. |
| `renderForm()` | `BuildAssuranceFormState` plus the Laravel view/Vue component. |
| `change()` | `UpdateAssuranceFormField`. |
| `checkFormOrCreate()` / `createForm()` | `GetOrCreateAssuranceForm`. |
| `nextDate()` / `previousDate()` | Client-side calendar navigation using explicit `YYYY-MM-DD` route values; no mutation endpoint. |
| `printPage()` | Print route rendering the same state in read-only form. |
| `bulkCheckExpiryDate()` | `AssuranceValueRepository::valuesForSampleAndMedia(Sample $sample, Collection $media)`. Return every requested media ID with an empty string fallback. |
| `checkExpiryDate()` | `AssuranceValueRepository::valueForAnalysisAndMedia()`. Its unused legacy `$newDate` argument is not carried forward. |
| `getOutOfDateInfo()` | `AssuranceValueRepository::warningForAnalysisAndMedia()`. |
| `updateOutOfDateHere()` | `UpdateAssuranceExpiryEvidence`. |
| `updateExpiryDate()` | `UpdateConfirmationAssuranceValue`. Its unused legacy `$chainN` argument remains only UI context and is not part of persistence identity. |
| `updateFormByInnocDate()` | Resolve the day and invoke `SynchronizeAssuranceForm`. |
| `saveExplanation()` | `UpdateAssuranceExplanation`. |
| `checkFormReady()` | `RecalculateAssuranceForm`. |
| `checkEmptiesCountOnly()` / `checkEmpties()` | One query service returning count and dated rows for the cooldown-filtered incomplete list. |

`legacy/app/shared/calculation.class.php` also calls `bulkCheckExpiryDate()` to populate `confMediaTht`. Preserve that non-UI consumer. Add the assurance repository to the modern calculation context/cache input wherever migrated calculators need confirmation-media THT. Fetch all requested media values in one query and include them in result cache invalidation; do not make one query per medium.

### Models

Create:

- `app/Models/AssuranceForm.php`
- `app/Models/AssuranceFormField.php`
- `app/Models/AssuranceFormUser.php`
- `app/Models/AssuranceFormExpiredSample.php`
- `app/Models/AssuranceFormRevision.php`

Extend `app/Models/Sample.php` with `assuranceForm()` and extend `app/Models/Media.php` only with relationships that are actually consumed.

### Domain actions

Keep controllers thin. Suggested actions and responsibilities:

| Action | Responsibility |
| --- | --- |
| `ResolveAssuranceDay` | Convert timestamps/date strings using `Europe/Amsterdam`; return canonical date and local-midnight epoch. |
| `GetOrCreateAssuranceForm` | Acquire `Cache::lock('assurance-form:YYYY-MM-DD')`, create one form, snapshot active users, seed only new-form fields, link day samples. |
| `SynchronizeAssuranceForm` | Idempotently recompute day samples/media/supplements, activate/deactivate fields, clean evidence, and recalculate completeness. This is the job handler's source of truth and may also be called synchronously. |
| `BuildAssuranceFormState` | Produce API state for the standalone page, including sections, warnings, missing list, revisions, and print state. |
| `UpdateAssuranceFormField` | Validate the field belongs to the form, preserve string value, create the exact revision, assess warnings, and recalculate. |
| `UpdateAssuranceExplanation` | Update explanation and recalculate without adding a legacy-visible revision. |
| `UpdateAssuranceExpiryEvidence` | Upsert/remove sample evidence from confirmation `inzet` and shared THT, then recalculate. |
| `RecalculateAssuranceForm` | Apply the exact completeness rules and persist `is_complete`. |
| `RefreshAffectedConfirmations` | Recalculate and broadcast enabled confirmations that use the changed day/media. |

Use database transactions and `lockForUpdate()` for mutations. Lock in a stable order: parent form, field, evidence rows, then affected confirmation rows. Form creation needs a cache lock because no parent row exists to lock yet.

### Form routes and controller

Add an `AssuranceFormController` under authenticated `can:assurance-form.view` routes. Keep route names explicit:

```text
GET    /laboratory/assurance-forms                         assurance-forms.index
GET    /laboratory/assurance-forms/{date}                  assurance-forms.show
POST   /laboratory/assurance-forms/{date}                  assurance-forms.store
PATCH  /laboratory/assurance-forms/{assuranceForm}/fields  assurance-forms.fields.update
PATCH  /laboratory/assurance-forms/{assuranceForm}/explanation
GET    /laboratory/assurance-forms/{assuranceForm}/print
```

Use Form Requests for date, field ID, and value shape. The permission currently means access to the legacy editable feature, so do not invent a new permission during this pass. Enforce the same project/sample lock semantics used by confirmation mutations where a confirmation-originated write is concerned.

The index response/page needs:

- selected date and previous/next navigation;
- form or not-found state plus create command;
- active day fields grouped as legacy sections;
- current warning state and explanation for each field;
- missing labels;
- incomplete forms older than the cvar cooldown;
- revision rows;
- a print-friendly rendering of the same state.

Add the actual Borgingsformulier link beneath the existing Borging navigation heading.

### Trigger integration

Use a queued propagation path for structural changes. Create `SynchronizeAssuranceFormJob` implementing `ShouldQueue` and `ShouldBeUniqueUntilProcessing`, and let it use the configured `default` queue by **not** calling `onQueue()`. Its payload is only the canonical `YYYY-MM-DD` form date; it must reload all current records when handled rather than serializing models or a precomputed media list.

The job contract is:

```php
final class SynchronizeAssuranceFormJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
  use Queueable;

  public int $tries = 5;
  public int $timeout = 60;
  public int $uniqueFor = 300;

  public function __construct(public string $formDate) {}

  public function uniqueId(): string
  {
    return $this->formDate;
  }

  public function middleware(): array
  {
    return [
      (new WithoutOverlapping('assurance-form:'.$this->formDate))
        ->releaseAfter(2)
        ->expireAfter(75),
    ];
  }
}
```

`ShouldBeUniqueUntilProcessing` coalesces duplicate jobs waiting for the same day but releases its unique lock before execution. A change committed while one job is running can therefore enqueue a follow-up pass. `WithoutOverlapping` serializes those passes. The action itself must remain idempotent because queue delivery is at least once.

Dispatch exactly one job per affected day after the **outermost** mutation transaction commits:

- a sample receives its first inoculation timestamp;
- a sample’s inoculation timestamp changes: synchronize old and new day;
- a sample is removed: synchronize old day;
- an analysis is added or removed for an inoculated sample;
- confirmation decision changes to or from enabled;
- confirmation `in_use` changes for chain/support media.

The project currently sets queue connection `after_commit` to `false`, so every dispatch must explicitly use:

```php
SynchronizeAssuranceFormJob::dispatch($formDate)->afterCommit();
```

For sample lookup research changes, dispatch from the outer orchestration boundary in `UpdateLookupResearch`, not once from each nested `AddResearchProfileToSample`, `CreateSampleAnalyses`, or `RemoveSampleAnalysis` call. Adding one research profile may create many analyses and should still enqueue one day job. Reordering analyses does not change media membership and must not enqueue one.

Distinguish instantiated sample work from administrator catalog changes:

- Adding, revising, deactivating, or removing an assay definition does nothing by itself because no day owns an assay until a `sampleanalysis` row references that exact `assay_base` revision.
- Normal assay edits create a new revision and leave existing analyses pointing to the old revision; do not resynchronize historical days.
- `UpdateAssay` with `force_changes=true` mutates the referenced revision in place. For that exceptional path, query distinct inoculation days of existing `sampleanalysis.assay_base = assay.id` rows and dispatch one job per day after commit.
- Adding, revising, or deactivating a research-profile definition does not alter already-instantiated sample analyses; do not enqueue a form sync.
- Assigning a research profile to a sample creates concrete analyses and therefore enqueues one job for that sample’s inoculation day. Removing its concrete analyses does the same.
- Editing `media.hasDate`, `media.type`, or `media.supplements` in place affects existing forms. Dispatch one job for each distinct active form day that currently references that medium, using a small coordinator query/job if the fan-out is large.

Do not hide dispatch in model observers during the transplant. Invoke it from application actions so old/new dates, the outer transaction, and no-op operations remain explicit.

### Consistency boundary

The background job is the normal propagation mechanism, but correctness must not depend on queue latency:

- `AssuranceFormController::show()` synchronously calls `SynchronizeAssuranceForm` before building state, matching legacy `renderForm()`.
- `GetOrCreateAssuranceForm` synchronizes the new form before returning it.
- Confirmation THT/support mutations synchronously ensure the one affected form field exists, update it, and recalculate the current confirmation before returning.
- A queued full-day pass follows structural confirmation changes and refreshes all other affected analyses.
- The job may finish after the initiating HTTP response. Until then an already-open form can be stale, but opening/reloading it self-heals synchronously. Broadcast an assurance-form-updated event only if live refresh of an already-open form is implemented; do not make Reverb a correctness requirement.

This hybrid keeps potentially broad profile/analysis synchronization off the request path while preserving read-your-write behavior for analysts. A queue-only design is not acceptable because an unavailable worker would otherwise leave the form and confirmation readiness stale indefinitely.

## Confirmation viewer integration

### Current placeholders to replace

The current application deliberately leaves the boundary ready:

- `PendingConfirmationAssuranceFields` returns `{kind: assurance_placeholder, available: false, required: false, value: null}`.
- `UpdateConfirmationMetadata` rejects keys matching `{mediaId}_tht`.
- `ConfirmationRacetrack.vue` renders THT disabled with `Niet beschikbaar`.
- `ConfirmationDialog.vue` renders support media as checkboxes only, while legacy also rendered their THT/material value.

### Provider contract

Replace `PendingConfirmationAssuranceFields` with `DatabaseConfirmationAssuranceFields`. Change the contract so it receives enough context to resolve the day, preferably:

```php
public function fields(SampleAnalysis $analysis, int $mediaId, array $media): array;
```

Pass the analysis from `ConfirmationStateBuilder` through `ConfirmationRacetrackEvaluator`. Load `sampleRecord.assuranceForm.fields` once in the state builder; do not query once per step.

For `media.hasDate != 1`, return no field. Otherwise return one field:

```php
[
    'key' => $mediaId.'_tht',
    'kind' => (int) $media['type'] === 3 ? 'material' : 'date',
    'available' => $analysis->sampleRecord has a valid inoculation timestamp,
    'required' => true,
    'value' => the day form field value or '',
    'acceptable_range' => $media['acceptable_range'] ?? null,
    'out_of_spec' => boolean,
    'out_of_date_here' => boolean,
    'out_of_date_text' => legacy warning text,
    'explanation' => string,
]
```

If the sample has an inoculation date but no form, the field is available with an empty value. The write action creates and synchronizes the form. If the sample has no valid inoculation date, it is unavailable and not required, and mutation returns the legacy-equivalent Dutch error that the sample has no inzetdatum.

For chain media, include the THT field only for reached steps, as the evaluator does now. It is required when present. For support media, expose a value field alongside the checkbox; it is required only when that support medium is active. Material support uses a text input and acceptable-range assessment instead of a date control.

### Mutation behavior

Keep one metadata endpoint if that minimizes frontend churn, but route `*_tht` keys to a dedicated `UpdateConfirmationAssuranceValue` action before the normal confirmation metadata path.

The action must:

1. Use `ConfirmationMutation`-equivalent project lock checks.
2. Parse and verify the media ID exists in the assay’s chain or support configuration.
3. Verify the field is currently reached or active for the selected scope.
4. Resolve/create the sample’s inoculation-day form and ensure the medium is in scope.
5. Store the value in `b3_{mediaId}` on the form, not in confirmation metadata.
6. Add the type-13 assurance revision using the medium name.
7. Read that step’s `{mediaId}_inzet` from confirmation metadata and update this sample’s expiry evidence.
8. Recalculate the assurance form.
9. Recalculate all affected confirmations for the day/media, invalidate their result caches, and dispatch calculation jobs after commit.
10. Return the normal `ConfirmationResource` state so the Pinia mutation queue remains unchanged.

When `{mediaId}_inzet` changes through the existing metadata action, also recompute expiry evidence against the current shared THT. `aflees` and control changes do not affect assurance evidence.

Add a small endpoint/action for saving the explanation from the confirmation dialog. It writes to the assurance field, not the confirmation row, and returns refreshed confirmation state.

### Readiness effects

Update `ConfirmationRacetrackEvaluator` so available assurance fields participate in `metadataComplete`:

- reached chain THT is required;
- active support THT/material is required;
- inactive support is not required;
- `nvt` is non-empty and therefore satisfies the value requirement;
- an out-of-spec/evidence field additionally requires a non-empty explanation;
- an unavailable field caused by no sample inoculation date must not make an otherwise non-applicable confirmation loop forever, but mutation remains unavailable.

Do not use a stale `_tht` value found in imported `confirmations.metadata`. The day form wins, matching legacy `checkExpiryDate()` and `recheckReadyStatus()`.

### Vue changes

In `ConfirmationRacetrack.vue`:

- remove the hardcoded disabled `Niet beschikbaar` state;
- render `date` assurance fields with the same `d-m-Y` behavior as the standalone form;
- render `material` as text and show its acceptable range without changing the stored value;
- apply red/orange states from backend booleans rather than reimplementing the rules in JavaScript;
- expose the explanation button only when required by backend state.

In `ConfirmationDialog.vue`:

- replace checkbox-only support rows with a compact row containing active checkbox, medium name, and assurance value control;
- keep the value visible when inactive, but do not require it;
- add an explanation dialog/editor using the assurance field returned by the API.

In `confirmationStore.js`:

- reuse the existing serialized mutation queue;
- add `saveAssuranceValue(mediaId, value)` and `saveAssuranceExplanation(mediaId, explanation)` actions;
- apply the returned full confirmation state;
- do not optimistically calculate warning/readiness state.

## Legacy import/upsert command

Create `app/Console/Commands/ImportLegacyAssuranceFormsCommand.php` with this interface:

```text
legacy:import-assurance-forms
    {file=legacy/imports/assuranceforms.csv : Legacy assuranceforms CSV export}
    {--revisions= : Optional changetracker CSV export}
    {--dry-run : Parse and report without writing}
```

Invoke it only through Sail:

```bash
vendor/bin/sail artisan legacy:import-assurance-forms legacy/imports/assuranceforms.csv --dry-run
vendor/bin/sail artisan legacy:import-assurance-forms legacy/imports/assuranceforms.csv --revisions=legacy/imports/changetracker.csv
```

Do not add assurance forms to `ImportLegacyCsvDirectoryCommand::IMPORTS`: that command truncates registered tables and cannot expand normalized child rows safely.

### Required preflight

Before any write, scan the complete CSV and report line-specific errors for:

- missing required headers `id,date,data,is_complete`;
- duplicate IDs in the file;
- duplicate canonical form dates, including collisions already in the target database under another ID;
- nonnumeric, negative, or non-midnight timestamps;
- malformed JSON;
- non-object top-level JSON;
- blocks with an unexpected scalar shape;
- malformed dynamic keys and references to absent media/sample records;
- malformed `users_available_at_time` entries;
- revision rows whose `assurance_form` is absent.

Unknown JSON keys are warnings, not failures, because `legacy_data` preserves them. Duplicate dates are failures requiring operator resolution; do not guess which form wins.

`--dry-run` performs all preflight and transformation accounting with zero writes. Print counts for forms, fields by section/kind, user snapshots, expiry evidence, revisions, unknown keys, and failures.

### Upsert algorithm

After successful preflight, use one database transaction for the import unless production volume proves that impractical. Daily forms are expected to be small enough for this.

For each row:

1. Convert `date` to `form_date` in `Europe/Amsterdam` and retain the original string.
2. Upsert `assuranceforms` by `id`; fail if that ID currently represents another day.
3. Preserve imported `is_complete` exactly. Do not recalculate during import.
4. Store the exact source `data` string in `legacy_data`.
5. Upsert user snapshots by `(form_id, user_id)`.
6. Expand blocks 0 through 3 into exact `field_key` rows. Attach block-4 explanations by full field key.
7. Expand block 5 into parent-media/sample evidence rows with barcode snapshots.
8. Delete and rebuild only normalized child rows belonging to that imported form, so reruns also remove children that were removed from the source JSON.
9. Do not delete target forms absent from the CSV.
10. Import only `changetracker.type = 13` revisions and upsert them by `legacy_id`.
11. Backfill `samples.assurance_form_id` by canonical day without changing `sample_innoculated`.
12. Advance the database sequence/auto-increment beyond the highest preserved ID for every table receiving explicit IDs.

The same file imported twice must produce the same runtime rows and counts. A failure must roll back all writes and return `Command::FAILURE`.

### Mapping details

- Block 0 keys become `b0_{key}`. Set `kind=user` for `beheer`, `afgewogen`, `ingezet`, `gegoten`; `instoof` is `time`; unknowns are `legacy`.
- Block 1 nested keys become `b1_{field}_{duration}`, retain `duration`, and infer user/date/time kind from the field name.
- Block 2 keys become `b2_{key}` with `kind=date` for `tht_*`; unknowns are `legacy`.
- Block 3 numeric keys become `b3_{mediaId}`. Infer `material` from current `media.type = 3`, otherwise `date`.
- Block 3 `extra_{mediaId}_{supplementId}` keys become `b3_extra_{mediaId}_{supplementId}`, with parent `media_id` and the exact supplement ID string.
- Block 4 values attach to their matching field. If no field exists, create an inactive `legacy` field so the explanation is not lost.
- Block 5 keys create evidence under the parent media ID. Preserve the stored barcode rather than replacing it from the current sample.

## Implementation order

Keep each phase runnable and avoid changing confirmation behavior before the provider exists.

1. Create the assurance migrations and models, including `samples.assurance_form_id`.
2. Implement day resolution, value assessment, form creation, synchronization, and completeness actions.
3. Implement the import command and run `--dry-run` against a real export before writing data.
4. Import forms and optional assurance revision rows; inspect reported warnings before continuing.
5. Implement standalone controller, requests, resource/state builder, routes, Blade/Vue screen, navigation link, and print view.
6. Add synchronization calls to sample/analysis/confirmation lifecycle actions.
7. Replace the pending confirmation provider and pass analysis/form context through the evaluator.
8. Route THT mutations and explanations into assurance actions; add affected-confirmation recalculation and broadcasts.
9. Add support-media value controls and warning/explanation UI.
10. Complete sample audit-trail rendering from normalized assurance relations.
11. Remove only the placeholder text and rejection branch made obsolete by the working provider. Keep the interface as the boundary.

Suggested generation commands, all through Sail:

```bash
vendor/bin/sail artisan make:migration create_assurance_form_tables --no-interaction
vendor/bin/sail artisan make:migration add_assurance_form_id_to_samples_table --table=samples --no-interaction
vendor/bin/sail artisan make:model AssuranceForm --no-interaction
vendor/bin/sail artisan make:model AssuranceFormField --no-interaction
vendor/bin/sail artisan make:model AssuranceFormUser --no-interaction
vendor/bin/sail artisan make:model AssuranceFormExpiredSample --no-interaction
vendor/bin/sail artisan make:model AssuranceFormRevision --no-interaction
vendor/bin/sail artisan make:controller AssuranceFormController --no-interaction
vendor/bin/sail artisan make:command ImportLegacyAssuranceFormsCommand --no-interaction
```

## Manual verification checklist

No automated test work is requested in this pass. Before declaring implementation complete, verify these flows manually with representative imported and newly-created records:

- Import dry-run reports counts and writes nothing.
- Running the real import twice changes no values or row counts on the second run.
- Legacy IDs, source JSON, user snapshots, hidden old fields, explanations, evidence, and revisions survive import.
- A sample started near a DST transition attaches to the expected Amsterdam calendar day.
- Starting the first sample creates one form; concurrent creation cannot create two forms.
- Adding/removing analyses updates normal media; enabling/disabling confirmation updates only in-use confirmation media.
- Removing the last sample hides/deactivates the form without deleting imported history.
- New forms show only `ingezet`, `gegoten`, PFZ, PFZ tubes, BPW, and dynamic media/material fields.
- Imported legacy-only general and duration fields remain visible/required exactly when present.
- Regular media, confirmation media, supplements, and materials appear in their legacy sections.
- `nvt`, invalid date strings, equal-date THT, expired THT, malformed material ranges, and comma decimals match the rules above.
- Missing-list labels and the cooldown list match legacy output.
- Red/orange warning state and explanation visibility are identical on the form and confirmation viewer.
- A confirmation THT edit is visible immediately in another analysis from the same inoculation day.
- Changing confirmation `inzet` adds/removes only that sample’s evidence marker.
- Chain THT and active support THT/material values affect confirmation readiness; inactive support does not.
- A sample without an inoculation date cannot save assurance THT and receives the Dutch error.
- Locked/authorized work cannot be mutated.
- Print and sample audit views show the same values and explanations as the interactive form.

Run only implementation checks needed for delivery:

```bash
vendor/bin/sail artisan migrate --pretend
vendor/bin/sail artisan legacy:import-assurance-forms legacy/imports/assuranceforms.csv --dry-run
vendor/bin/sail bin pint --dirty --format agent
vendor/bin/sail npm run build
```

## Definition of done

The transplant is complete when the standalone Borgingsformulier is usable by date, old records are imported losslessly and idempotently, daily media synchronize from current sample/analysis/confirmation relationships, completeness and explanation behavior match legacy, sample audit output remains available, and the existing confirmation viewer reads and writes the same shared day/media values without storing THT in confirmation JSON.

Do not optimize or rename the remaining legacy schema as part of this work. Once production parity is established, removal of `legacy_data`, stricter date validation, stronger foreign keys, and broader audit modernization can be considered separately.