# Account settings

Authenticated users can open **Accountinstellingen** from the avatar menu to
manage their personal details, system alias, profile photo, Dutch/English
signatures, and password, with inner settings navigation but without the main
application section sidebar.
Email cannot be changed here. Administration and self-service both edit
`users.alias`, the single case-insensitive alternate login identifier.
Conflicting emails and aliases are rejected.

The implementation uses the existing `profiles` relationship and columns.
The additional nullable, unique `users.alias` column is introduced by
`2026_10_20_152516_add_alias_to_users_table.php`.

The subsequent consolidation migration copies profile usernames only where the
alias is empty, preserves existing aliases, and drops `profiles.username`.
Discarded usernames no longer work for login. Conflicting login identifiers
abort the migration without changing data. Back up the database first: this
consolidation cannot be reversed without restoring discarded usernames from a backup.

- Avatars are cropped with Cropper.js and re-encoded on the server as 256 × 256 PNGs.
- Signatures retain the legacy filenames `signatures/{user_id}.png` (Dutch) and
  `signatures/{user_id}En.png` (English). Each language can be replaced or deleted
  independently. Images are fitted within 1600 × 600 pixels without upscaling.
- Images use Laravel's private `local` disk and authenticated, owner-only
  endpoints. No public storage link is required. Existing signature files must
  be copied to the same relative filenames on that disk when migrating legacy
  assets; the settings page does not migrate files from the legacy application.
- Uploads accept JPG, PNG, GIF, and WebP, up to 5 MB and 4096 × 4096 pixels.
- Password changes reuse the Fortify password action, require the current
  password and confirmation, and are rate-limited.

After deploying, run migrations and build assets through Sail:

```sh
vendor/bin/sail artisan migrate --no-interaction
vendor/bin/sail npm ci
vendor/bin/sail npm run build
```

Run the focused regression suite with:

```sh
vendor/bin/sail artisan test --compact tests/Feature/AccountSettingsTest.php
```

# Report generation

Project search and project overviews link to **Rapport genereren** for users with
both `projects.view` and `reports.generate`. Authorisation continues to use the
existing project authorisation and frozen-result workflow, including vetoes and
confirmations.

Deploy the reporting schema and register the initial template without reseeding
users:

```sh
vendor/bin/sail artisan migrate --no-interaction
vendor/bin/sail artisan db:seed --class=ReportConfigurationSeeder --no-interaction
vendor/bin/sail npm run build
```

Under **Beheer / Rapportage**, configure **Rapportsjablonen**, their
**Analyse-associaties**, and **Rapportvoetteksten**. The initial renderer supports
`maz2020`; other imported template definitions remain administrable but cannot
yet produce PDFs. A template must be active and associated with every assay
revision in the project, including non-reportable assays. New assay revisions
inherit associations without removing historical ones.

The page retains the legacy three-column arrangement: project details,
order-specific preferences, previous PDFs and output settings on the left;
parameter/order tabs and sample notes in the middle; report style, language,
violation settings and execution on the right. The default parameter order
interleaves analysis positions across samples, as the legacy sorter did.
Templates are filtered by language and single-sample/overview layout. For
`maz2020`, select individual reports or a combined report with **1 monster per
pagina**. Unsupported layouts are not silently substituted.

The style selector only lists active, supported templates associated with every
assay in the project. Association and footer configuration belong in Beheer,
not in the analyst's selector. Footer and signature checks still run when
generating a report. It never automatically approves assays for a template. The existing
`legacy:import-csv` command now also registers `docgentemplates.csv`,
`docgenassociations.csv`, and `footers.csv`, preserving imported IDs and text.
**That command replaces all registered import tables**, not just reporting
tables; use it only with the complete migration dataset and an appropriate backup.

Import the approved certificate text into `footers` unchanged. Its selection
uses the legacy `name` (template folder), `lang`, and `q` columns. The seeder
deliberately does not supply replacement ISO wording. A missing footer is an
explicit generation error. Authorised certificates also require the authorising
user's Dutch signature on the private local disk at `signatures/{user_id}.png`;
the port does not substitute an unsigned certificate or an invented error image.

**Voorbeeld bekijken** renders a watermarked PDF in a new tab without issuing it.
**Rapport maken** retains
the legacy issuance metadata and, for authorised projects, advances print
versions and sets the project export flags. The PDF and `exports` records are
stored together with the existing type-10 audit event. Individual PDFs share a
transaction and each receive a print version; a failed batch rolls back its
metadata and removes files created by that attempt. Existing files are never
overwritten. The raw PDF opens directly, or in a small PDF listing for individual
reports. Email, sending, and revision management are not part of this pass.

Existing non-empty report references are preserved. For a project without one,
generation uses the same `MESA_PROJECT_REFERENCE` resolver as bulk registration:
project ID (1), first sample ID (2), or first sample barcode (3). A blank override
uses the cvar's configured default. A preview does not persist that reference;
successful creation does. An unresolvable reference is an explicit error rather
than a certificate with an empty number.

Existing report files should be imported to the private local disk as
`reportStorage/{hash}.pdf`. Legacy table names, identifiers, and export metadata
are retained. No public storage link is needed.

Reporting preferences use the existing client-portal service. An unavailable
portal produces a visible warning rather than silently claiming preferences
were loaded. The request uses `projects.portal_id`, not the LIMS project ID.
Orders without customer preferences (including LIMS-originated orders) display
the legacy defaults: Dutch, client-level violation highlighting, individual
reports for normal samples and combined reports for special types. A
**Opnieuw laden en toepassen** action fetches the order's current preferences
without discarding the sample selection or parameter ordering. It retains the
user's settings if the portal request fails. To enable the external preferences link, configure
`GLASS_REPORT_PREFERENCES_URL` with the portal's actual UI URL; `{client}` and
`{project}` are replaced with the LIMS client ID and portal project ID.

Temporary reports: **Voorlopig rapport genereren** (the clock icon in the project overviews, or
**Voorlopig rapport** on the report page) issues a report while analyses are
still unfinished; these appear as *Niet afgerond*. It reuses the report
generator and `ProjectReportForm` with a `temporary` flag, on
`/laboratory/projects/{project}/reports/temporary`, with the same permissions.
The legacy limits apply:

- Only templates whose folder is listed in the JSON array in the cvar
  `DOCGEN_TEMPLATES_ALLOWED_TEMP` are offered (and accepted). A missing or invalid
  value allows none. The legacy value lists only `mazOverview`/`mazOverviewEn`,
  which cannot yet produce PDFs, so add `maz2020` (or later ported templates) to
  the cvar to use the feature meanwhile.
- The report is always one combined PDF of the whole project, with every parameter
  in the default order. Parameter selection, ordering, and per-sample or selected
  reports are not available and are ignored server-side.
- Creating it stores an export with `temporary = 1` and `type = 2`
  (*Voorlopig rapport*), advances the print version of an authorised project, and
  then withdraws the project's authorisation like a manual deauthorisation
  (revision +1, frozen results cleared, portal informed with the preview flag).
  The change tracker records this as "Project authorisatie ingetrokken, bron
  wijziging: Voorlopig rapport, reden:…" and, as in legacy, no "Project
  uitgevoerd" event or export flag is set. A project that was not authorised is
  left unchanged apart from the export.
- **Voorbeeld bekijken** shows a watermarked PDF and changes nothing.

Run the reporting regressions through Sail:

```sh
vendor/bin/sail artisan test --compact tests/Feature/ReportAdministrationTest.php tests/Feature/ProjectReportGenerationTest.php
```

The actual-PDF wording test uses `pdftotext` and reports a skip if it is absent.
Install the optional inspection tools inside Sail to run that check:

```sh
vendor/bin/sail exec -u root laravel.test apt-get update
vendor/bin/sail exec -u root laravel.test apt-get install -y --no-install-recommends poppler-utils
```