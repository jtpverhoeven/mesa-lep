# Sample lookup

Open `/laboratory/samples/lookup`, optionally with `?barcode=26091000`.
The page follows the three-column layout in `legacy/app/views/samples/lookup.php`
within the current application theme.

## Implemented

- Barcode lookup, previous/next sample by ID, and navigation within the project.
- Sample/client/project details, ordered custom fields, legacy extra fields,
  project-relative sample numbering, registration and stored status information.
- Research grouped by profile group in legacy project/follow-number order.
- Add research using the existing profile/roaming selector and settings dialog.
- Remove individual analyses and move analyses up/down using existing actions.
- Stored completion percentage, predicted completion date, and read-only notes.
- Shared Pinia state and reusable lookup research/debug components.

`samples.view` is required for lookup. Adding requires `samples.assign-research`;
removing/reordering requires `samples.update-research`. Locked or authorized
projects cannot be changed. Analyses with stored results, readiness, or
confirmation activity cannot be removed by this initial implementation.
Repeated assay assignments remain supported by the existing legacy actions.

## Placeholders

Result input, result calculation/units, confirmations, revisions, dilution and
reference editing, sample-detail/note editing, portal metadata, product groups,
THT operations, documents, and collaborative user-presence locks are not implemented.
The result panes and placeholder controls show sample, sampleanalysis, assay,
profile, and roaming IDs where applicable. They do not send result or portal
requests. Existing notes and progress values are displayed, not recalculated.
The transactional edit locks are not a user-presence/checkout system.

## Concurrency

Registration already uses `pg_advisory_xact_lock` inside the transaction before
reading the last sample and allocating a barcode. The unique `uq_samples_barcode`
index is the database-level guarantee against duplicates, including writes that
do not follow that allocation protocol. Keep the unique-index migration applied;
legacy duplicate data must be resolved before applying it. A collision caused by
barcode configuration or non-cooperating writers is rejected, not silently retried
with a different barcode.

Lookup research changes lock the sample and project in one transaction and roll
back the entire selection on failure. Submitted reorder IDs must include every
current analysis exactly once. Frontend writes are single-flight and navigation
is disabled while saving. Read requests use generation IDs so stale barcode or
research-option responses cannot overwrite newer state.

## Checks

```sh
./vendor/bin/sail artisan test --compact tests/Feature/SampleLookupTest.php
./vendor/bin/sail node --test tests/Unit/sampleLookupStore.test.js
./vendor/bin/sail npm run build
```

The feature tests use an isolated SQLite database. They verify the unique-index
constraint, but do not simulate simultaneous PostgreSQL registration processes.