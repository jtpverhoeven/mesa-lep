# Report sending (extern page, e-mail templates, portal hand-off)

Status: phases A, B and C implemented. `reports.send.mail` (`SendReportTransaction`) and `projects.send-files.mail` (`SendProjectFiles`) post through `ClientPortalService`; the Vue submit flow (`SenderSubmit.vue`) posts the payload from section 5 (the file sender posts `includeSampleFiles` and `messageFiles`). Notes: unauthorised transactions return 422 (allowed improvement); a missing PDF is skipped like legacy; document sends are recorded whenever the portal was reachable (the response is not inspected), and an empty selection answers `{"message":"empty"}`.
Audience: the agent that implements it. Everything below is derived from the legacy code in `legacy/app/` and from the current Laravel code base.

## 0. Ground rules

- **The legacy behaviour is the specification.** Labels, texts (Dutch), default selections, show/hide rules, confirmation prompts, failure semantics and the order of side effects must be reproduced exactly. Only minimal UX improvements are allowed (see section 9).
- **Legacy data is imported as-is.** Keep the legacy table and column names (`exports`, `emailtemplates`, `samplefiles`, `filehistories`, `portal`) and the legacy value semantics (e.g. `exports.date` is a unix timestamp string, `sent_to` is JSON).
- Follow the existing architecture: one invokable-style action class per use case in `app/Actions/<Area>/`, thin controllers, Form Requests, `ClientPortalService` as the **only** portal HTTP boundary (its base URL is `services.glass.base_url`; Accept and bearer come from `PortalSettings`).
- Use Eloquent relationships and query bindings. The legacy code concatenates ids and hashes into SQL (`implode("','", $hashes)`); never port that.
- Run through Sail (`vendor/bin/sail artisan ...`), PHPUnit, Pint after PHP changes.

## 1. Glossary and legacy data model

| Term | Meaning |
| --- | --- |
| Export / report revision | One row in `exports` = one generated PDF (or zip) for a project revision. |
| Transaction | Group of exports created in one generation run. The id of the first row becomes `exports.transaction` for all rows of the run. Can be `NULL` on very old rows. The extern page is addressed by transaction. |
| Extern page | `exportsController::extern($transaction)`: the page where a report is previewed and e-mailed to the client via the portal. |
| File sender | `exportsController::fileSender($projectId)`: the same idea for sample files only (no report). |
| Orphaned file | A visible sample file of the project that is not part of the samples covered by the reports in the transaction. |
| Portal | The external client portal ("glass"). It owns the contact lists (groups) and does the actual e-mail delivery. |

### 1.1 `exports` (report revisions)

Columns: `id, transaction, client, revision, project, print_version, type, naming_strategy, naming_description, template, hash, date, report_reference, true_reference, temporary, was_authorized, sample_id, sample_id_scope`.

- `type`: 1 = Rapport, 2 = Voorlopig rapport, 3 = Rapport per monster.
- `hash` = sha256(projectId . revision . print_version . auth_status). The file is `reportStorage/{hash}.pdf` (Laravel: `Storage::disk('local')`, `Export::storagePath()`).
- `date`: unix timestamp (string).
- `was_authorized`: 0 when the report was generated while the project was not authorised. **Such reports can never be sent.**
- `sample_id_scope`: JSON list of sample ids covered by the report. `sample_id`: single sample for type 3. Old rows may have neither (backwards compatibility path, see 4.7).
- `template`: template folder name; a trailing `En` means the report is English (drives the default mail language, see 4.3).
- Old type-3 rows with a `NULL` transaction may be `.zip` files.
- Report name (`generateReportName($row, $full)`): `reference.revision.print_version`, plus ` - naming_description` when `naming_strategy == 'description'`; with `$full` it is prefixed `"{client name} - MAZ-L "`; result is `stripPathForbidden(truncate(..., 200))`. Laravel has `Export::filename()`; verify it matches before reusing.

### 1.2 `emailtemplates`

`id, name varchar(128), template text (NL), template_en text, bcc text, defaultmail int, defaultmail_with_files int, defaultmail_for_files int`.

The three flags are exclusive per type: at most one row has each flag = 1.

| Template type (`defaultFor`) | Column | Used for |
| --- | --- | --- |
| `reports` | `defaultmail` | report without sample files |
| `report_with_files` (see gotcha 6.1) | `defaultmail_with_files` | report with sample files attached |
| `for_files` | `defaultmail_for_files` | loose files (file sender and orphaned files) |

### 1.3 `samplefiles` and `filehistories` (already in Laravel)

- `samplefiles`: `visible_for_client` (0/1), `sent_to_client` (0/1), `hash_name`, `sample_id`, file name, upload date. Blob in `sample-files/{hash_name}` on the local disk.
- `filehistories`: one row per send. `samplefile_id`, `sent_by` (user), `sent_method` (`report` | `filesender`), `transaction_id` (NULL for filesender), `sent_to` JSON `{"groups": "A;B", "extra": "..."}`, date.
- "Verstuurd, N keer" = count of `filehistories` for that `samplefile_id`; "Nog niet verstuurd" when 0.

### 1.4 Portal (`portal` table, `PortalSettings`)

Contains connection settings. Legacy also has `portalBCC` and `portalMessage` ("Standaard email" in `views/portal/email.php`), but this screen is not linked from the admin sidebar and `getEmailSettings` is unused. **Do not port it.** `templates/export/email_nl.php` is also generated in `extern` but never used.

Contact groups are not stored locally: `GET contact-lists/{client}` returns `[{id, name, members:[{name,email}], ...}]`.

## 2. Current Laravel readiness audit

Present:

| Need | Where |
| --- | --- |
| Generate reports, create `Export` rows, transaction handling | `app/Actions/Reports/GenerateProjectReports.php` (`export()`), `app/Models/Export.php` |
| Inline PDF of one export | route `projects.reports.show` (`ProjectReportController`) |
| Placeholder page for a transaction (list + iframe) | `projects.reports.batch` / `resources/views/reports/created.blade.php` (**to be replaced by the extern page**) |
| Report history per project (grouped by transaction) | `GetProjectReportOptions::history()` + `ProjectReportForm.vue` |
| `Project::exports()`, `Project::sampleFiles()` (through samples, ordered by barcode, then id) | `app/Models/Project.php` |
| SampleFile / FileHistory models, `visibleForClient` scope, `histories()` | `app/Models/SampleFile.php`, `FileHistory.php` |
| Writing delivery bookkeeping (`sent_to_client`, history row) | `app/Actions/SampleFiles/RecordSampleFileDelivery.php` (methods `report` / `filesender`) |
| Portal calls: `reportMailer`, `documentMailer`, `reportMailerNotificationDispatch`, `getContactLists`, `getReportingPreferenceForProject` | `app/ClientPortal/ClientPortalService.php` |
| Language + `activated_contact_groups` preferences | `app/Actions/Reports/GetReportingPreferences.php` |
| Keyring type `Export` and `Extern`, acquire/release actions and client store | `app/Keyrings/KeyringType.php`, `app/Actions/Keyrings/*` |
| Change tracker; type 21 label "Project naar portal geëxporteerd" | `app/ChangeTracking/*` |
| Permissions `projects.view`, `reports.generate`, `settings.advanced` | `app/Support/PermissionCatalog.php` |

Missing (to build):

1. `emailtemplates` migration, model, factory, seeder, admin UI (phase A).
2. Extern page, file-sender page, `checkTransactionIsMixed`, file-history modal endpoint, zip-all download, "other versions" block for unauthorised reports (phase B).
3. `mailer` and `documentMailer` endpoints and actions with portal failure handling (phase C).
4. Navigation entries (project overview "send" button, admin menu "Uitgaande email").

## 3. Phase A: back-end readiness and e-mail template administration

### 3.1 Readiness checklist (do first, small PRs)

1. **Transaction lookup**: add `Export::scopeInTransaction(int $transaction)` (or an action `GetReportTransaction`) returning exports ordered by `id`, with `project` and client name eager-loaded. Legacy joins `projects` (reference, portal_id) and `clients` (name). Add relation `Export::project()` (`belongsTo(Project::class, 'project')`) and `Export::client()` if missing. Throw/return a typed "not found" for 0 rows (legacy: `Error: Kon rapport-transactie database rij niet vinden.`).
2. **PDF existence check**: `Export::pdfExists()`; legacy dies with `Error: Kon rapport PDF niet vinden in reportStorage` if any PDF of the transaction is missing.
3. **Sample scope resolver** (action `ResolveExportSampleScope`): returns the sample id list of an export: `json_decode(sample_id_scope)` if present, else `[sample_id]` if set, else `[]`.
4. **Visible file queries** (action `ListProjectClientFiles`), exact legacy semantics:
   - `visibleProjectFiles(project)`: sample files of the project's samples with `visible_for_client = 1`, ordered by `samples.barcode ASC` (then id for determinism). Must work with zero samples (legacy breaks on `IN ()`).
   - `visibleProjectFilesInReport(project, sampleIds)`: same, restricted to samples that are in the project **and** in `sampleIds`.
   - Each file gets `sent_label` and `sent_count` (`withCount('histories')`).
5. **`Export` fixture/factory** with `transaction`, `sample_id_scope`, `was_authorized`, plus a fake PDF on the fake disk, for tests.

### 3.2 Database

Create `emailtemplates` with the legacy column names and types (migration named like the other legacy-port migrations; see `database/migrations/*create_sample_file_tables.php` for the style). Keep `defaultmail`, `defaultmail_with_files`, `defaultmail_for_files` as integers (not booleans) with default 0. No unique constraint (legacy enforces exclusivity in code, and imported data may be inconsistent); the "selected" logic must therefore tolerate multiple rows with flag = 1 (first wins, ordered by id).

Model `EmailTemplate` (`$table = 'emailtemplates'`, `$timestamps = false`), factory with states `defaultForReports()`, `defaultWithFiles()`, `defaultForFiles()`. Import CSV mapping follows the existing `legacy/imports/*.csv` convention.

### 3.3 Admin screens (legacy: `emailTemplatesController`, `views/emailTemplates/*`)

Menu: "Uitgaande email" under the Client Portal group of the admin menu (legacy `adminSidebar.php`; Laravel `resources/views/layouts/app.blade.php` lines ~47-49). Legacy has no privilege rule for this controller. **Decided:** template administration is gated with `can:settings.advanced` (like the other portal admin pages); sending (extern page, file sender, `mailer`, `documentMailer`, history modal, `checkTransactionIsMixed`, downloads) uses `can:projects.view` + `can:reports.generate`, like the existing report routes. No new permissions are needed.

Screens and behaviour:

- **Index** (`emailTemplates/index`): table with radio selector, id, three Ja/Nee columns titled `Standaard rapport email`, `Standaard rapport email (met bestanden)`, `Standaard email voor bestanden`, and name. Empty state text: `Geen emails ingesteld`. Sidebar actions: add, edit (selected row), remove (confirm dialog, legacy uses a bootbox confirm), set as default (asks for which of the three types).
- **Edit/create** (`emailTemplates/edit/{id?}`): fields `Naam`, `Email` (NL textarea, 10 rows), `Email engelse versie`, `BCC`. Unknown id: redirect to the index.
- **Store**: create or update. Validation: name required (max 128). Nothing else is enforced in legacy; templates may be empty.
- **Destroy**: no protection when the row is a default (legacy). After deletion, no template is default for that type; the selector must keep working (see 3.5).
- **Set default** (`setdefault(id, defaultFor)`): `defaultFor` is `reports`, `reports_with_files` or `for_files`. Zero the whole column for all rows, then set it for the chosen row (exclusive, in a transaction).
- **loadTemplate** (`emailTemplates/loadTemplate/{id}/{lang}`): JSON `{template, bcc}`. `template_en` when `lang != 'nl'`, otherwise `template`.

Actions to create (`app/Actions/EmailTemplates/`): `ListEmailTemplates`, `SaveEmailTemplate`, `DeleteEmailTemplate`, `SetDefaultEmailTemplate`, `LoadEmailTemplate`, `BuildEmailTemplateSelector`. Routes under the existing admin group, with route names `email-templates.*`.

### 3.4 `renderSelector` semantics (shared by extern and fileSender)

`BuildEmailTemplateSelector::handle(string $type, ?int $forcedId, string $lang)` returns `{options: [{id,name,selected}], template, bcc}`:

- options = all templates (legacy order: by id).
- selected = the forced id when given; else the first template whose flag column for `$type` is 1; else the first template. (Legacy quirk: if forced id is set it wins even when not default.)
- `template` is the selected template's body in `$lang` (nl or en), `bcc` the selected template's bcc.
- Types and columns: `reports` -> `defaultmail`, `report_with_files` -> `defaultmail_with_files`, `for_files` -> `defaultmail_for_files`.
- Empty table: no options, empty template and bcc. The extern page must still render.

### 3.5 Tests (phase A)

Feature tests per action; index lists defaults; set-default is exclusive for each of the three types; `loadTemplate` language switching; selector fallback (no default -> first; none at all -> empty); delete of a default template; authorisation (403 without the permission).

## 4. Phase B: external sender page

### 4.1 Routes (suggested)

| Legacy | Laravel |
| --- | --- |
| `exports/extern/{transaction}` | `GET laboratory/reports/{transaction}/send` (`reports.send.show`), replaces the placeholder `projects.reports.batch` redirect target |
| `exports/exportContentViewer/{id}` | existing `projects.reports.show` (inline PDF) |
| `exports/downloadRevision/{id}` | `GET .../reports/{export}/download` |
| `exports/downloadZip/{transaction}` | `GET .../reports/{transaction}/zip` |
| `exports/checkTransactionIsMixed/{transaction}` | `POST` JSON |
| `exports/mailer` | `POST` (phase C) |
| `exports/fileSender/{project}` | `GET laboratory/projects/{project}/send-files` |
| `exports/documentMailer` | `POST` (phase C) |
| `fileHistories/renderHistory` | `POST` JSON/HTML fragment, input `sampleFileId` |
| `emailTemplates/loadTemplate/{id}/{lang}` | phase A |

`GenerateProjectReports` currently ends on the placeholder `reports.created` view; once the extern page exists, redirect to the extern page for the transaction (legacy redirects there after generating a non-temporary report). Temporary reports (type 2) go through the same page; sending them is allowed when `was_authorized = 1`.

### 4.2 Loading (`ShowReportSender` action)

1. Resolve exports by transaction (3.1). 0 rows -> error `Error: Kon rapport-transactie database rij niet vinden.` Any PDF missing -> `Error: Kon rapport PDF niet vinden in reportStorage`. Render as an error page (404/500 style) with that text.
2. Project = project of the first export (reference, `portal_id`, client).
3. Request keyring lock on type `Export` for the **project id** (not `Extern`; legacy uses `requestLockAndStatus('EXPORT', project)`) using `AcquireKeyring`. Locked by someone else: show a lock notice with that user's name and avatar and **hide the whole export panel** (viewer and form). Not locked: the page releases the lock when it closes (legacy: `navigator.sendBeacon(removeOwnLockById)` on `beforeunload`); use the existing keyring client store/release-by-id route.
4. `zip_download_show` is true only when the transaction has more than one export.
5. `hasUnauth` = any export has `was_authorized == 0` -> unauthorised mode (4.5).

### 4.3 Language and preferences

- Default mail language (`'nl'` or `'en'`): based on the **first export's `template`** name; a trailing `En` means `en`, otherwise `nl`. (Changed in legacy on 04-03-2026: it is based on what was generated, not on the portal preference.)
- Portal preferences via `GetReportingPreferences` (`translateReportingPreferences(portal_id, clientInfo)`): provides `activated_contact_groups` (the preselection). 404 or `foundSettings == false` -> defaults (empty preselection).

### 4.4 Page layout (reproduce)

Top: project info and lock notice. Panel with:

- Report selector (`<select>` with one `<option>` per export, label = `generateReportName($row, false)`) driving an `<iframe>` that shows the inline PDF, and a "Download" button (downloadRevision for the selected export). First export selected initially.
- If `zip_download_show`: button "Alles downloaden" (zip of the transaction) plus the "Welke rapporten?" select (`all` = alle rapporten, `onlySelected` = alleen geselecteerde) and a multi-select list of the reports (used for `selectedReports`).
- Email form with three tabs: **Adressering**, **Bericht**, **Overig**.
  - Adressering: contact groups (4.6), `extraTo`, `cc`, `bcc` inputs.
  - Bericht: up to three message blocks (4.8).
  - Overig: `transaction` (read-only), checkbox "backToOverview" (checked by default), submit button.
- Sample files tables (4.7) next to the message.

### 4.5 Unauthorised reports (`hasUnauth`)

Hide the e-mail form and show a block "Verzenden uitgeschakeld" explaining that the project was not authorised when the report was generated. Below it: table of other versions (`projectReportOverviewCondensed`: unique transactions of the project, newest first; envelope button to the extern page hidden when `transaction` is NULL; date formatted `d-m-Y` from the unix timestamp; content = links to each report labelled `project.reference.revision.print_version`) and buttons to open the project and to go to the completed / authorised overviews. `GetProjectReportOptions::history()` already builds nearly the same data; extract into a shared action and add the envelope link.

The server-side `mailer` also refuses (see 5.2); never rely on the UI hiding alone.

### 4.6 Contact groups

- Fetch from the portal: `ClientPortalService::getContactLists(clientId)`. Not the local DB.
- Render one checkbox per group (legacy `templates/contactgroups/group.php`, class `.cgroupbox`) with a popover listing members (name + email).
- Checked when the group is the only group, or its id is in `activated_contact_groups`.
- Portal unreachable: show the page with an inline error and an empty list (legacy would throw); the user can still use `extraTo`.
- `fileSender` shows all groups with **no** preselection.

### 4.7 Sample files integration

Collected in `BuildReportSenderFiles`:

- `sampleIds` = union of the scopes of all exports in the transaction (3.1 item 3).
- No sample ids: `attachments = visibleProjectFiles(project)`, `orphaned = []`.
- Otherwise: `attachments = visibleProjectFilesInReport(project, sampleIds)`; `orphaned = visibleProjectFiles - attachments`, compared by `hash_name`.
- Each table has columns: Barcode, Bestandsnaam, Status, Upload datum, Meesturen.
  - Status: "Nog niet verstuurd" or "Verstuurd, N keer" plus a "see" button opening the history modal.
  - Meesturen: checkbox per file with a "[wisselen]" toggle-all link in the header. Nothing is checked by default.
- Attachments and orphans are rendered as separate tables; each section is hidden when empty. Empty everywhere: "Geen zichtbare bestanden gevonden".
- History modal (`fileHistories/renderHistory`, POST `sampleFileId`): table with columns Datum, Verzonden via (`Samen met rapport` for `report`, `Via bestands mailer` for `filesender`), Verzonden door, Contactgroepen, Extra ontvangers. A `report` entry links to the extern page of its `transaction_id`.

### 4.8 Message blocks and template selection (client-side behaviour)

Three containers, each with its own template selector, language switch (nl/en) and `<textarea>`:

| Container | Posted as | Template type | Shown when |
| --- | --- | --- | --- |
| `#normalEmailContainer` | `message` | `reports` | reports without selected files exist (see below) |
| `#attEmailContainer` | `messageAtt` | `report_with_files` | some reports have selected files |
| `#orphanedEmailContainer` | `messageFiles` | `for_files` | orphaned files are selected |

`checkEmailAttachmentState()` runs whenever file checkboxes change and POSTs `selected_files` to `checkTransactionIsMixed/{transaction}`. The endpoint returns `true` when, for the selected files, some reports in the transaction have selected files and others do not ("mixed"). Rules:

- mixed and files selected: show normal + att.
- not mixed and files selected: show only att.
- no files selected: show only normal.
- orphan container: shown iff at least one orphaned file is selected.

Template behaviour:

- Initial templates come from the selector (3.4) with the default language of 4.3; att and files containers are initialised with `nl`/`en` in the same way.
- Changing the template loads `loadTemplate/{id}/{lang}`, replaces the textarea content and **overwrites `#bcc`** with that template's bcc. Initial `#bcc` = bcc of the `reports` default.
- **Legacy bug, fix it (decided):** the att selector loads its template using the language switch of the **normal** container (`#mailTemplateSelectorLang`) when its template changes, so changing the att template ignores the att container's own nl/en switch. In the port, each container's template selector must use its own language switch (normal -> normal, att -> att, files -> files). Initial values stay as described above.
- The message is raw text (Markdown compatible); do not HTML-escape on the way to the portal.

### 4.9 Submit (`doMail()`)

1. No contact group checked -> `confirm("Geen contact-groep geselecteerd, toch doorgaan met versturen?")`; cancel aborts.
2. Hide the submit button, show a progress modal.
3. POST `mailer` with: `transaction`, `receivers` (checked group ids), `extraReceiver` (`extraTo`), `cc`, `bcc`, `message`, `messageAtt`, `messageFiles`, `exportAll` (true when "Welke rapporten?" = all or no zip UI), `selectedReports`, `includeSampleFiles` (hashes of checked attachments), `includeOrphanedSampleFiles` (hashes of checked orphans).
4. Response `{status, message}`:
   - `status == true`: if "backToOverview" is checked (default) redirect to the authorised projects overview (legacy `projects/viewAuthorised`), else show "Emails succesvol afgedragen aan portal".
   - `status == false`: show `message`, which is a semicolon-prefixed list of the `true_reference`s that failed; re-enable the form.

### 4.10 Standalone file sender (`fileSender($projectId)`)

- Entry points: project search detail menu item "Monster bestanden mailen" (legacy `projects/search.php`), and the "no output" page (`views/exports/nooutput.php`) shown when report generation yields no report but the project has visible files.
- Unknown project: redirect to the completed overview.
- Page: all contact groups without preselection, `for_files` template selector with language switch, bcc prefilled from the `for_files` default, files table of **all** visible project files (same columns and history modal as 4.7), "backToOverview" checkbox (default checked; redirects to the project in the project search, legacy `projects/search/{id}`).
- Submit: if no file selected -> alert `U dient minstens 1 bijlage te selecteren…` (use the exact legacy text from `views/exports/fileSender.php`). Else POST `documentMailer`.
- No keyring lock and no authorisation check in legacy; keep the lack of a lock, but apply the permission gate.

## 5. Phase C: portal interaction

`ClientPortalService` already implements all portal calls with the legacy multipart field names. Do not add a second HTTP client.

### 5.1 Portal endpoints and payloads

| Method | Endpoint | Fields |
| --- | --- | --- |
| `reportMailer` | `POST mail-report` | `emails` (JSON array of contact list ids), `extraMail`, `cc`, `bcc`, `message`, `data` (base64 PDF), `md5` (of the file), `client`, `hash`, `project`, `project_name` (= `report_reference`), `project_date` (= `exports.date`), `revision`, `print_version`, `true_reference`, `temporary`, `true_file_name`, `includeSampleFiles` (JSON of hashes) |
| `documentMailer` | `POST mail-document` | `emails`, `extraMail`, `cc`, `bcc`, `message`, `client`, `sample`, `project`, `sampleFileHashes` |
| `reportMailerNotificationDispatch` | `POST notify-reports` | `hashes` (JSON), `client` |
| `getContactLists` | `GET contact-lists/{client}` | |

Verify each field against `ClientPortalService` before wiring (the service exists, parity was the aim). Legacy logs portal responses to `apiLog.txt`; the service already has request logging.

### 5.2 `SendReportTransaction` action (POST `mailer`)

Exact legacy order:

1. Load exports by transaction.
2. If any export has `was_authorized == 0`: abort. Legacy returns nothing; return a 422 JSON `{status:false,message:...}` (allowed UX improvement), do not call the portal.
3. Contact lists are fetched once from the portal for the whole run.
4. For each report:
   - Skip when not `exportAll` and its id is not in `selectedReports`.
   - Name = `generateReportName($report, true)` (used for `true_file_name`).
   - If the PDF exists: md5 + base64 of the file; `trueReference` and client id from the project.
   - Scope = `ResolveExportSampleScope`; `filesInThisReport = visibleProjectFilesInReport(project, scope)`; `applicableFileHashes = hash_name(filesInThisReport) ∩ includeSampleFiles`.
   - Message = `messageAtt` when `applicableFileHashes` is not empty, else `message`.
   - Call `reportMailer(...)` with the posted receivers, extra, cc, bcc.
   - **Failure semantic**: response JSON `message` exists and `!= 'ok'` -> append `';' . trueReference` to the failures string; nothing else is recorded for that report.
   - **Success**: build `groupNames` = names of the selected groups joined with `;` (names looked up in the contact lists by id; unknown -> empty string); then
     1. `setAsPreviouslySent(applicableFileHashes)`: `samplefiles.sent_to_client = 1` (use `RecordSampleFileDelivery`);
     2. one `filehistories` row per file: `sent_to = {"groups": groupNames, "extra": extraReceiver}`, `sent_method = 'report'`, `transaction_id`, `sent_by` = current user;
     3. a change-tracker event of type **21** on the project: `Project verstuurd naar portal-mailer. Contact-groepen: {groups}, Extra ontvanger: {extra}, CC: {cc}, BCC: {bcc}  ({report_reference}.{revision}.{print_version}). Bestanden verstuurd mee-verstuurd: {file names}`. Copy the exact string from `exportsController::mailer`; do not paraphrase.
5. Orphaned files (`includeOrphanedSampleFiles` not empty): group hashes by sample (`groupSampleHashesBySample` -> `sample_id => [hashes]`) and for each sample call `documentMailer(receivers, extra, cc, bcc, messageFiles, client, sample, project, hashes)`; on success `setAsPreviouslySent`, history rows with `sent_method = 'filesender'` and `transaction_id = NULL`, and a type 21 event `Document verstuurd naar portal-document-mailer ...` (exact text from legacy).
6. If at least one portal call was made, call `reportMailerNotificationDispatch(all hashes of the transaction's exports, clientId)`, even when some sends failed.
7. Return `{status: (failures === ''), message: failures}`.

### 5.3 `SendProjectFiles` action (POST `documentMailer`)

- No `includedSampleFileHashes` -> `{"message":"empty"}`.
- Group by sample; per sample: `documentMailer`, then `setAsPreviouslySent`, one history row per file (`filesender`, `transaction_id` NULL), type 21 event `Document verstuurd naar portal-document-mailer ...`.
- Respond `{"message":"ok"}`.
- Legacy does not inspect the portal response here; keep that for success, but wrap exceptions (5.5).

### 5.4 Validation (Form Requests)

Legacy trusts the client. Add only non-behavioural validation: transaction exists, `receivers`/`includeSampleFiles` arrays of scalars, `cc`/`bcc`/`extraReceiver` strings (do not validate as emails: legacy accepts free text such as `a@x.nl;b@y.nl`, and the portal splits it). Hashes must belong to visible files of the project (this is a security improvement and does not alter behaviour for valid use); unknown hashes are dropped.

### 5.5 Error handling

`ClientPortalService` returns `Response` objects. The action must treat the following as a failed send (append the reference to failures, no bookkeeping): `ConnectionException`, non-2xx status, body that is not JSON, JSON without `message`... **Careful**: legacy treats a missing `message` key as success (only `message` present and `!= 'ok'` is a failure). Keep that for 2xx JSON responses; the new strictness applies to transport failures only. Log failures.

### 5.6 Legacy "development mode" guard: removed

Legacy skipped `reportMailer`, `documentMailer` and `notify-reports` when `$_SESSION['DEVELOPMENT']` was set. **Decided:** do not port it. Staging and development use their own `.env` files (portal base URL and bearer from `PortalSettings`), so no send-disabling flag, config key or code path is added. `ClientPortalService` is used as-is.

### 5.7 Queueing

Legacy is synchronous (PDF is base64-posted in the request and the user waits behind a progress modal). Keep synchronous for parity; the `status`/`message` response depends on it. Consider timeouts: the service uses a 30 s timeout; a large report per transaction may need `set_time_limit`-like handling (`max_execution_time` for the route).

## 6. Quirks and gotchas to preserve or consciously handle

1. Template type naming: `emailTemplates::index/setdefault` use `reports_with_files`, while `isDefaultMail` and `extern` use `report_with_files`. Map both to `defaultmail_with_files` in one place (an enum `EmailTemplateType`) and cover with a test.
2. The lock is on keyring type `Export` with the project id, not `Extern`.
3. Default language comes from the generated template name, not from the portal preference.
4. `bcc` is overwritten by every template change.
5. `mailer` silently does nothing in legacy when a report is unauthorised; we return an error (allowed improvement).
6. `notify-reports` is sent for **all** hashes in the transaction, not only the sent ones.
7. `visibleProjectFiles` must work with zero samples.
8. `exportContentViewer` falls back from zip to pdf and prints `Zip bestand.` for a type-3 export with `NULL` transaction; `downloadRevision` serves the PDF if type is 1 or 2 or the transaction is not NULL, otherwise the zip; `downloadZip(transaction)` zips all PDFs of a transaction as "N rapportages uit MAZ-L ref.rev" with duplicate-name suffixing. These are needed for the viewer/download buttons.
9. Orphan sends reuse `messageFiles`; report sends use `message`/`messageAtt`.
10. `includeSampleFiles` is posted per transaction, so each report only sends the intersection with its own scope; a file belonging to a sample covered by two reports is sent with both and gets two history rows.
11. No local contact-group tables are used anywhere in the sending flow; `contactgroups`/`contactgroupmembers` in the legacy schema are not read here.
12. Unused legacy bits not to port: `extern($exportableId)` parameter, `$email` from `email_nl.php`, `portal/email` settings screen.

## 7. Suggested layout

```
app/Actions/EmailTemplates/{ListEmailTemplates,SaveEmailTemplate,DeleteEmailTemplate,SetDefaultEmailTemplate,LoadEmailTemplate,BuildEmailTemplateSelector}.php
app/Actions/Reports/{ShowReportSender,ResolveExportSampleScope,BuildReportSenderFiles,CheckTransactionIsMixed,SendReportTransaction,DownloadReportZip}.php
app/Actions/SampleFiles/{ListProjectClientFiles,SendProjectFiles,RenderSampleFileHistory}.php   (reuse RecordSampleFileDelivery)
app/Enums/EmailTemplateType.php
app/Models/EmailTemplate.php
app/Http/Controllers/{EmailTemplateController,ReportSenderController,FileSenderController}.php
app/Http/Requests/{SaveEmailTemplateRequest,SendReportRequest,SendProjectFilesRequest}.php
resources/views/email-templates/*.blade.php
resources/js/pages|components/ReportSender.vue, FileSender.vue (OpenVue, shared Mesa theme; no unstyled/local skin)
```

## 8. Test plan

- Phase A: see 3.5.
- Extern page: 404 text for unknown transaction; error when a PDF is missing; language derivation (`...En` template); lock shown for another user and form hidden; unauthorised block hides the form; contact-group preselection (single group, preselect list) with `Http::fake` for the portal; attachments vs orphans split; sent labels "Nog niet verstuurd" / "Verstuurd, N keer"; `checkTransactionIsMixed` for mixed/not mixed/no selection.
- Mailer (`Http::fake`): success path writes `sent_to_client`, one `filehistories` row per file with the right JSON/method/transaction, type 21 event text; `message != 'ok'` yields `status:false` with `;reference`, no bookkeeping, notification still dispatched; unauthorised export -> no portal call; `exportAll=false` honours `selectedReports`; `messageAtt` only when applicable files; orphan flow with `filesender` histories and `NULL` transaction; connection exception handling.
- File sender: `empty` response, grouping by sample, one portal call per sample.
- Each test uses factories (`Export`, `SampleFile`, `EmailTemplate`) and the fake disk.

## 9. Allowed minimal UX improvements

- Clear error response (instead of a silent return) when sending an unauthorised report.
- Inline error when the portal contact lists cannot be loaded.
- Disable the submit button while sending (legacy hides it).
- Keyboard/focus accessibility of the dialogs, in both themes, via the shared Mesa components.

Anything else (labels, defaults, flows, order of side effects) stays as in legacy.

## 10. Implementation order and acceptance

1. Phase A readiness (3.1) and `emailtemplates` migration/model/admin (3.2 - 3.5). Acceptance: admin can CRUD templates, exclusive defaults work, `loadTemplate` JSON is correct.
2. Phase B: extern page read-only first (viewer, downloads, lock, unauthorised block, files tables, history modal), then the message containers and contact groups, then `checkTransactionIsMixed`, then the standalone file sender. Acceptance: pages match legacy behaviour with the submit action stubbed.
3. Phase C: `SendReportTransaction` and `SendProjectFiles`, wire `doMail()`, redirect behaviour, portal failure handling. Acceptance: tests in section 8 pass and a manual run against a portal sandbox produces identical `filehistories` rows, `sent_to_client` flags and change-tracker events as legacy for the same input.
4. Wire navigation (extern link after generation, envelope in the report history, "Monster bestanden mailen", admin menu "Uitgaande email") and replace `projects.reports.batch`.

Decisions (settled): permissions are `settings.advanced` for template admin and `projects.view` + `reports.generate` for sending (3.3); the development-mode guard is removed (5.6); the att-selector language bug is fixed (4.8).
