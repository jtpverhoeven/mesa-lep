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

Run `php artisan migrate` against the intended Laravel database. The migration
creates new tables; it does not import records or adopt pre-existing legacy tables.
Back up and reconcile any existing tables before applying it to such a database.

Run `php artisan test --compact tests/Feature/AssayAdministrationTest.php` for
isolated in-memory SQLite checks of column parity with the SQL dump, rollback,
model writes, defaults, raw payloads, and revision-aware relationships. These tests
do not exercise MySQL charset, collation, or length enforcement.