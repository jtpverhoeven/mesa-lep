# Assay administration foundation

The persistence structure comes from `legacy/app/sql_legacy.sql` and the legacy
assay administration controllers. The Blade-first MVP now includes login, the
LIMS/Beheer shell, a Vue account menu, an assay listing and creation form, and
analytical-base creation. Beheer requires the existing `administrator` role.

The HTTP controllers delegate writes to `CreateAssay` and `CreateAssayType` actions.
`StoreAssayRequest` validates assay input. Assay creation and matrix assignments
are transactional, including assigning the generated ID to `original_id`.

The administration flow includes assay editing as legacy revisions, analytical
base editing, and analytical result-field add/edit/delete administration. Media,
matrices and global custom fields already in the database can be selected.
Confirmation-chain configuration, project/sample-field start-anchor lookup,
media/matrix administration, and script execution remain outside this pass.
New assays have confirmation disabled. Duration remains in days; meta-assay
references remain comma-separated IDs. Scripts are stored as text only.

## Included tables and models

| Legacy table | Eloquent model | Purpose |
| --- | --- | --- |
| `assays` | `Assay` | Assay definitions and their revisions |
| `assaytypes` | `AssayType` | Analytical bases |
| `assaytypefields` | `AssayTypeField` | Base-specific result fields |
| `assayfields` | `AssayField` | Global custom-field definitions |
| `media` | `Media` | Media configuration referenced by assays |
| `matrix` | `Matrix` | Matrix definitions |
| `matrixcontent` | `MatrixContent` | Matrix assignments shared by assay revisions |

`assayprofiles` links assays to research profiles; it is not an assay definition
table and is deferred with research-profile administration. Portal mappings,
sample analysis, results, and reporting are also outside this pass.

## Compatibility

- Table names, column names (including `dillution` and `hasDate`), signed integer
  IDs, lengths, nullability, explicit SQL defaults, and MySQL collations are retained.
- No timestamps, soft-delete columns, unique constraints, or foreign keys are added.
  Legacy references can remain unresolved until their modules are ported.
- JSON-bearing columns remain strings, not JSON columns or Eloquent array casts.
  Supply legacy-encoded strings for `media_id`, `custom_fields`, confirmation
  configuration, and other structured payloads. Empty strings and JSON objects
  are not normalized. Script content is stored, never executed.
- `active` remains an integer field; no automatic filtering or deletion is added.
- `type_base` references `assaytypes.id`. `assaytypefields.test_id` also references
  that analytical base. Global `assayfields` describe keys in `custom_fields` and
  do not have a relational join to individual assays.
- `original_id` identifies the first assay revision. The legacy creation path
  inserts the assay and then assigns its generated ID to `original_id`. Future
  application logic performs those writes transactionally and supplies every
  required column without a SQL default in `CreateAssay`. No model events or
  implicit database defaults are introduced.
- `Assay::revisions()` includes the original and inactive revisions in ID order.
  `matrixcontent.assay_base` references the original assay ID, not `type_base`
  or the current revision ID. Matrix relationships therefore share assignments
  across revisions. Use `MatrixContent::matrixDefinition()` for the related model;
  the `matrix` attribute remains the raw legacy ID.
- `media_id` is a JSON list in a varchar column, not a single media foreign key.
  No conventional Eloquent media relationship or replacement pivot is introduced.
- Editing the current assay revision creates a new row with the same
  `original_id` and deactivates the previous row. Matrix assignments continue
  to use the shared original-assay key. Older revisions remain read-only in the
  administration UI, matching the legacy screen.
- Analytical result-field names remain alphanumeric and unique within their
  analytical base. Editing a result field changes only its alias, position,
  input filter, and end-result flag; its legacy name and type remain fixed.

## Applying and testing

### Research-profile reference fields

The research-profile editor shows selected analyses read-only. **Bewerken** opens
a draft dialog; applying changes updates the local profile, while cancelling
discards them. Saving the profile still creates a revision.

Workflow reference fields are declared in
`config/result-calculations.php` under `reference_outputs`, keyed by the
case-insensitive legacy `file:` identifier. Each entry maps an output name to its
display label, for example `['ph' => 'pH', 'temperature' => 'Temperatuur']`.
Add all expected reference outputs when introducing a workflow. These declarations
also cover legacy workflows whose calculators have not yet been ported; no legacy
PHP is executed to discover fields. A blank script uses the default calculator's
`result` output. Unknown or inline workflows show an explicit configuration warning
instead of guessing output names; existing reference keys remain editable.

Reference values retain their legacy `ref_<output>` keys in `assayprofiles.reference`.
Existing unprefixed keys such as `kve` are also retained and bind to the same
workflow output without generating a duplicate prefixed input.
An entered value, including zero, requires a reference source available to the
profile (global, or belonging to its client). Save, copy, and bulk actions enforce
this rule transactionally. Empty reference fields do not require a source.

Run `vendor/bin/sail artisan test --compact tests/Feature/ResearchProfileEditorTest.php`
for the editor payload, reference-source safeguards, and revision/copy regressions.
Run `vendor/bin/sail node --test tests/Unit/researchProfileEditor.test.js` for draft
isolation, output-field mapping, client-side validation, and source-loading races.

### Legacy CSV profile imports

Both `legacy:import-csv` and `db:import-csv` decode profile `dillutions` and
`reference` JSON before assigning those attributes to Eloquent. The shared
`DecodeCsvJsonAttributes` action also handles ordinary `array`/`json` casts;
untyped legacy JSON columns, such as assay `custom_fields`, remain raw strings.
Normal and double-encoded profile objects/arrays are accepted. Empty cells,
legacy null markers, and JSON null become empty maps. Storage remains a single
JSON object, including for empty maps; output keys, zero values, and empty
reference values are retained.

The directory importer preserves exported IDs, revisions, ordering, flags, and
reference-source IDs. It reports malformed or non-array profile JSON with its
file, CSV record number, and attribute, skips that row, and returns a failing
exit code rather than silently replacing the settings with `{}`. Valid rows
still import, matching the command's existing per-row failure handling.
Historical references are preserved even when their source is missing; the
editor's source validation still applies when those profiles are edited.

The directory importer streams each file and writes at most 500 rows in one SQL
insert/transaction, including the final partial batch. Eloquent's `fillForInsert`
prepares defaults, casts, and mutators before buffering each row, so the JSON
conversion above is retained without serializing values twice. A failed database
batch is rolled back and retried row by row using those same prepared values.
Bad rows are reported at their original CSV record number; good rows and earlier
batches are kept, and later files still import. PostgreSQL sequences are reset
after the import as before. Client anonymization also happens before batching.

Legacy bulk imports intentionally skip individual model lifecycle events
(`saving`, `creating`, `created`, `saved`). The generic `db:import-csv` command
continues creating Eloquent models individually and retains those events.

**The directory importer truncates all registered import tables, including when
only some CSV files are supplied.** It is for replacing a database from exports,
not merging into a database that has newer edits. Fixing the import conversion
does not repair already imported empty settings. Back up the database and use a
separately reviewed, ID-matched repair for those records instead of rerunning a
partial directory import.

Run `vendor/bin/sail artisan test --compact tests/Feature/ImportLegacyCsvDirectoryCommandTest.php tests/Feature/ImportCsvCommandTest.php`
for JSON conversion, legacy storage preservation, row-level failure reporting,
bounded SQL insert counts, retry handling, lifecycle behavior, and existing
printing-import compatibility.

Run `php artisan migrate` against the intended Laravel database. The migration
creates new tables; it does not import records or adopt pre-existing legacy tables.
Back up and reconcile any existing tables before applying it to such a database.

Run `php artisan test --compact tests/Feature/AssayAdministrationTest.php` for
isolated in-memory SQLite checks of column parity with the SQL dump, rollback,
model writes, defaults, raw payloads, and revision-aware relationships. These tests
do not exercise MySQL charset, collation, or length enforcement.