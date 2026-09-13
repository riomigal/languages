# Application Manual

This is the manual displayed inside the translation panel at the `languages.manual` route, normally `/translator/manual`. Use the generated section index to navigate. The four working screens are Languages, Translations, Translators, and Settings.

## Getting Started

### Install, publish, and migrate

The package requires PHP 8.2 or later within PHP 8 and Laravel 12 or 13, as declared in `composer.json`. Run installation commands from your Laravel application directory:

```bash
composer require riomigal/languages
php artisan vendor:publish --tag=languages-config
php artisan vendor:publish --tag=languages-migrations
php artisan vendor:publish --tag=languages-public
php artisan migrate
```

Composer auto-discovery registers the package providers. The config publish includes `config/languages.php` and `config/openai.php`. Runtime assets are published as `public/vendor/languages/css/app.css` and `public/vendor/languages/js/app.js`. Publishing `languages-translations` or `languages-views` is optional, for overriding the package's interface text or templates. The package also loads its migrations directly.

Migrations create the language, translation, translator, assignment, and settings tables, plus queue, batch, failed-job, and notification tables when missing. Set `LANGUAGES_DB_CONNECTION` and any custom table names in `config/languages.php` before migrating. A language matching `config('app.fallback_locale')` is created; that locale must exist in the package's supported language list.

New installations enable `db_loader` and disable multi-host coordination. Before serving translations, select a non-database cache store and import your source data as described below. For queued UI operations, configure an asynchronous queue connection and a worker consuming the configured package queue, normally:

```bash
php artisan queue:work --queue=languageProcessor
```

The cache store and queue connection are separate settings. A database-backed queue is compatible with a non-database cache.

### Sign in and change the default password

The default login URL is `/translator/login`. When no translator exists, the migration creates this administrator:

- Email: `admin@admin.com`
- Password: `aaaaaaaa`
- First and last name: `admin`

Sign in, open **Translators**, edit the administrator, and use **Update password** immediately. Set a password of at least eight characters and enter the same confirmation. Profile updates alone do not change passwords. Translator ID `1` cannot be deleted through the application, but its profile and password can be edited.

The login form has email, password, and **Remember me** controls. Invalid credentials remain on the login screen; login is limited to ten attempts per IP address per minute and shows the retry delay after that limit. Accounts use the package's `languages_translator` session guard. Use **Logout** in the navigation to end the session.

UI routes are registered only when `config('languages.main_server_domain')` exactly matches `config('app.url')`. The main-server value comes from `LANGUAGES_MAIN_SERVER_DOMAIN`, defaulting to the application URL. `LANGUAGES_ENABLED=false` disables the package's UI/API registration and custom loader. Route prefixes and screen paths are configurable in `config/languages.php`.

### First import

The **root language** is `config('app.locale')`. The **fallback language** is `config('app.fallback_locale')`; it is also the initial example language in the editor. These can differ.

1. Make your source translation files available under Laravel's language path, normally `lang/`. Check the root and fallback locale configuration.
2. Open **Languages** and run **Import Languages**. It recognizes supported locale directory names such as `lang/en/` and `lang/de/`. A JSON file such as `lang/de.json` alone does not create a language; add it using **Add Language** or provide its locale directory.
3. Wait for completion, refresh the language list, then run **Import Translations**. It imports PHP and JSON entries for language records already in the database, and translations from configured models. Set vendor and root-language import options first.
4. Run **Find Missing Translations** to create counterparts of root-language entries in the other languages. Review these rows, translate them, and approve them.
5. In DB-loader mode, approved application translations are served from the database. In file mode, export after approval. Model translations require export to their application model columns in either mode.

The initial CLI equivalent is:

```bash
php artisan languages:import-languages
php artisan languages:import-translations
php artisan languages:find-missing-translations
```

Imports add missing records; they do not overwrite existing database translations when a source file changes. File/model imports initially mark records approved and exported. Rows created by the missing-translation operation are unapproved, need translation, and are not exported, even when OpenAI supplies their text.

## Languages

### Browse, search, add, and delete

The list shows language code, name, and native name, sorted by code, with ten rows per page. **Search** matches those three fields. Use **View** on a row to open its Translations screen. Administrators see every language; other translators see only their assignments. Pagination provides previous, next, and numbered pages when needed.

**Add Language** opens a selector from the package's supported language list. Choose an unused code and press **Add**. Duplicate or unsupported codes are rejected. This creates a language record and, if absent, its locale directory under `lang/`, including when DB loading is enabled. It does not create translated entries. **Close** hides the form.

The row **Delete** action is shown only to administrators when `allow_deleting_languages` is enabled. It detaches translator assignments and deletes the language; its translation records are removed by the database's cascading foreign key. It does not delete language files or the locale directory, so a later filesystem import can recreate the language. There is no confirmation dialog in this action. Keep the fallback language if its values should be available as translation examples.

### Import and fill missing entries

All toolbar operations below are presented to administrators:

- **Import Languages** queues discovery of supported locale directories under the language path. Existing codes are skipped.
- **Import Translations** queues PHP, JSON, optional vendor, and configured model imports. The import covers the configured language set, not just the visible search results.
- **Find Missing Translations** queues creation of missing records by shared translation identifier. It uses the language matching `app.locale`, or the first language if that record is absent, as its source. Only source-language identifiers are copied to other languages; this does not scan application code for translation calls or merge keys from every language into the root. Existing counterparts are left alone. With OpenAI enabled, missing values are offered for automatic translation. Otherwise the source text is copied for later editing.

Vendor import reads published overrides under `lang/vendor/{namespace}/` before the registered package translation directory, so an already imported override is retained. PHP translation files must return arrays; JSON files must decode to arrays. Nested keys are flattened for storage, and PHP subdirectories are retained in the group name.

### Approve, export, and cancel

**Approve (All Languages) Translations** queues approval of every unapproved record in every language, regardless of list search or filters. Approval clears the needs-translation and updated flags, discards the saved old value, and records the acting administrator as approver. Bulk approval does not require a non-empty value, so review missing rows before using it.

With `db_loader` off, **Export All Languages** queues export for languages containing approved, not-updated, not-exported rows. It exports application/vendor files and model translations. If no eligible rows exist, a notification reports that nothing was exported.

With `db_loader` on, **Export All Translated Models** limits both the initial selection and each export job to model rows. PHP/JSON rows are left for DB delivery.

After a UI export batch finishes, enabled multi-host coordination requests forced exports on the other configured hosts. The normal export CLI does not perform this propagation.

**Delete running Batch (Jobs)** marks unfinished package batches cancelled and removes jobs from the package's database queue. With multi-host coordination enabled, it also requests cancellation on the configured other hosts. The result reports the local jobs/batches affected. See **Background Jobs and Queues** for cancellation limits.

## Translations

Open a language using **Languages > View**. The default route is `/translator/translations/{language}`, named `languages.translations`. A non-admin translator must be assigned to that language to open the screen.

### Table, search, and multi-select filters

The table displays ID, Vendor, Namespace, Group, Needs Translation, Approved, Approved By, Updated, Updated By, Exported, Key, Content, and Old Content. Updater and approver columns show names; filter choices show translator email addresses. Values are displayed as plain text; open **Translate** to edit the full value. The table has twenty rows per page.

**Search** matches ID, namespace, group, key, current value, and old value. A submitted search change resets pagination to page one.

Three multi-select menus refine the results:

- **Type**: PHP, JSON, or Model. Model entries represent configured Eloquent model translation columns.
- **Updated by**: translators recorded as the current updater.
- **Approved by**: translators recorded as the current approver.

Selecting several options in one menu matches any selected option. Different menus, state filters, and search are combined. Clear every selection in a menu to stop filtering that field. There is no separate selection for rows with no updater or approver.

### Five three-state filters

Open **State Filters**. Each button cycles through **gray: no restriction**, **green: true**, **red: false**, then back to gray. Each filters its stored boolean column:

- **Needs Translation** (`needs_translation`): a translation is requested or a missing counterpart was created. False means the request flag is clear, not necessarily that the row is approved.
- **Approved** (`approved`): the row is approved. False includes both requested/missing entries and edited text awaiting approval.
- **Updated** (`updated_translation`): an edited value is awaiting approval. This is a workflow flag, not a date filter or a permanent change history.
- **Is Vendor** (`is_vendor`): the row is marked as vendor content.
- **Exported** (`exported`): the database flag says the row is exported. This does not check the filesystem and is not required for DB-loader delivery.

For requested work, set Needs Translation to green. For review after editing, set Approved to red and Updated to green. For ordinary file export candidates, use Approved green, Updated red, and Exported red. These filters affect the table only; bulk approval/export uses its own full-language query.

Search submits after a short typing pause or on Enter. State filters and multi-select changes submit GET forms and reload the page, resetting pagination. URLs retain `search`, `page`, `needs_translation`, `approved`, `updated_translation`, `is_vendor`, `exported`, `types[]`, `updatedBy[]`, and `approvedBy[]`. A state button cycles the parameter from absent to `true`, then `false`, then absent. Bookmark, reload, and Back/Forward restore those selections. Translator assignment filtering uses `selectedLanguages[]`.

### Example language and translate modal

Select **Example Language** before opening a row's **Translate** action. This chooses the source example, not the language being edited. It defaults to `app.fallback_locale` when that language is permitted, or the current language otherwise. If the selected example is absent or empty, the editor uses the permitted fallback-language value.

The modal shows the translation identifier, example text, a textarea containing the current value, and expandable language-code summaries for available examples sharing its identifier. The selector, examples, and fallback lookup are all restricted to assigned languages for non-admins. Example text is escaped rather than rendered as HTML.

Use **Close modal** to dismiss the editor without saving its textarea changes.

### Update and OpenAI actions

**Update Translation** saves only when the textarea differs from the stored value. It saves the prior value and updater/approver information when beginning an edit, sets the acting translator as updater, clears the current approver, and marks the row updated, unapproved, and not exported. It also clears Needs Translation. Further edits before approval retain the same original old value. Saving does not automatically approve or export the text.

**Translate with OPEN AI** appears when OpenAI is enabled and a non-empty permitted example exists. It uses the actual example language, including after fallback, as the source. A suggestion fills an untouched textarea without saving. If you already edited the draft, or edit it while the request is running, the suggestion appears separately with **Use suggestion**. This keeps your draft until you explicitly replace it. Review the text and use **Update Translation** to save. Loading and failure states leave the editor usable; closing the modal cancels its pending request.

**Update & auto-translate others (OPEN AI)** is restricted to administrators and appears when OpenAI is enabled and the current language is the root language. If the value changed, it saves the root edit once and queues updates for the other existing translations sharing its identifier. It does not create absent counterparts; use Find Missing Translations first. The resulting updates remain unapproved and not exported.

OpenAI support is optional and requires `openai-php/laravel`, valid `config/openai.php` API settings, and the feature toggle. The package sends source text to OpenAI and asks it to preserve Laravel placeholders such as `:name`; review the result. `languages.open_ai_model` selects the requested model and `languages.max_open_ai_missing_trans` controls missing-translation request chunk size. Failed requests, a missing integration, or responses with missing/extra keys or non-string values preserve the service's original input. In the modal that input is the example text, so an unchanged source sentence is not proof of successful translation. Missing languages or null input return unchanged without a request; array requests retain empty strings and `"0"`.

### Approve, request, remove request, and restore

The following row controls are shown to administrators:

- **Approve** appears for an unapproved row with a non-empty current value. It accepts the current text, records the approver, clears Needs Translation and Updated, removes Old Content and the saved previous updater/approver references, and invalidates translation cache entries. It does not write files or set Exported to true. The string `"0"` is a valid value. The row button is absent for an empty value; bulk approval has no such check.
- **Request translation** appears when Needs Translation is false. It sets Needs Translation true and Approved false. It does not save an old value or send an email by itself.
- **Remove translation request** appears when Needs Translation is true. It sets Needs Translation false and Approved true. It leaves the other flags unchanged; it is not equivalent to the full Approve action.
- **Restore** appears for an unapproved row with a saved Old Content value, including an empty string or `"0"`. It restores that value and the prior updater/approver, clears the saved history and Updated flag, and marks the row approved and exported. It does not write files or explicitly clear Needs Translation. There is one saved old value, not a revision history; after approval clears it, Restore is unavailable.

### Approve and export a whole language

**Approve ({language code}) Translations** queues approval of all unapproved rows for the current language, including rows outside the current filters and rows with empty values.

In file mode, **Export Language** queues export of approved rows with Updated false and Exported false, including eligible model translations. In DB mode, **Export Translated Models** limits both the eligibility check and export job to model rows. Neither button forces re-export of already exported records. Use the CLI `--force=1` option when a re-export is needed. These toolbar controls are shown to administrators.

## Translators

### Browse and filter accounts

This screen, normally `/translator/translators`, is restricted to administrators. It shows ID, first name, last name, email, phone, admin status, and assigned language names, with ten accounts per page.

**Search** matches ID, first name, last name, email, or phone. The language multi-select matches translators assigned to **every selected language**. Clearing it removes that restriction. An admin's automatic access to all languages does not give it assignment records, so an admin may be excluded by an assignment filter.

### Create, edit, assign languages, and delete

Use the create-form button to enter email, phone, first name, last name, password, password confirmation, language assignments, and the Admin switch. Email must be valid and unique; first and last names need at least two characters. Phone is optional; a supplied phone must be unique. Passwords need at least eight characters and matching confirmation.

A non-admin requires at least one valid language assignment. Duplicate or nonexistent assignment IDs are rejected. Admin accounts can be saved without assignments and can access all languages. Explicit assignments still determine which language notifications an account receives.

**Create** saves a new account. **Edit** opens an existing account; **Update** saves profile, admin status, and the selected assignment list. Saving replaces the existing assignments with that list. **Close** hides the form. Account creation does not send an invitation or password email.

Use row **Delete** to remove an account. Translator ID `1` has no Delete control and its delete endpoint rejects the action. Other deletions submit directly without a confirmation dialog.

### Password changes and pending notifications

While editing an existing translator, **Update password** opens a separate password form. Enter a new password and matching confirmation, then use its update button. **Close** returns to the profile form. This administrative password change does not ask for the current password. There is no self-service password-change or reset screen for non-admins.

When `enable_pending_notifications` is enabled, the edit form displays the pending-translation notification button. It checks each explicitly assigned language and queues a notification when that language has rows with `needs_translation=true`. Delivery uses both email and the database notification channel. It does not count all unapproved rows, and an assignment with zero requested translations produces no delivery. Configure the application's mail transport and run the package queue worker. The success toast confirms the request, not email delivery.

Automatic reminders use a separate command and toggle, described in the CLI reference. Enabling either notification setting does not send an invitation, change assignments, or approve translations.

## Settings

The Settings screen, normally `/translator/settings`, is restricted to administrators. It displays the configured main-server domain as read-only text, plus eight toggles and the Domains field. Change the main-server value through `LANGUAGES_MAIN_SERVER_DOMAIN` or application configuration.

Each field has its own POST form and validation. With JavaScript, a toggle change or leaving the Domains input submits that field and reloads Settings. Without JavaScript, use the field's Save button. An invalid field does not block saving an unrelated valid field. The server refreshes cached settings after each successful write. Inline errors keep the attempted value for correction; reloading again shows the stored state. Enabling multi-host requires saved Domains, so save Domains first.

### Settings reference

These are the nine user-editable columns. Defaults below describe a fresh installation, not a saved upgrade configuration.

- **`db_loader`**, default `true`: selects the database translation loader instead of Laravel's ordinary file loader. Use it to publish approved application translations directly from the database without routine file export. Disable it when runtime language files are the required delivery format. The UI and normal CLI exports use model-only export when it is on; see the documented forced-export exceptions. Existing installations retain their saved true or false value when the default changes.
- **`import_vendor`**, default `false`: includes registered vendor namespaces during translation import. With DB loading enabled and this setting off, namespaced vendor translations continue to use the parent file loader. Turning it on makes those namespaces use database translations too, so import them before relying on DB delivery. Use it when translators should manage package/vendor text. It does not delete previously imported vendor records when disabled or filter existing vendor rows out of file exports.
- **`enable_open_ai_translations`**, default `false`: enables OpenAI calls for missing-translation generation and editor actions, and exposes the relevant buttons. Use it after configuring the optional integration. It does not automatically translate every existing row or approve generated text; enabling it without the integration leaves the service returning its input.
- **`enable_pending_notifications`**, default `false`: shows the manual pending-notification action in the translator edit form. Use it when administrators should request reminders for individual accounts. It does not schedule reminders and is not checked by the automatic notification command.
- **`enable_automatic_pending_notifications`**, default `false`: allows the automatic pending-notification command to iterate every translator's explicit assignments. Use it with your own command schedule. It works independently of `enable_pending_notifications`; the package does not register an active schedule for it.
- **`import_only_from_root_language`**, default `false`: limits translation imports, including model/vendor import passes, to the language matching `app.locale`. Use it when root-language sources are authoritative and other languages are maintained in the panel. It does not restrict Import Languages or delete existing non-root rows. Find Missing Translations can subsequently create root-language counterparts in the other languages.
- **`allow_deleting_languages`**, default `false`: exposes the language Delete links to administrators. Enable it when removing language records and their translations is intentional. The delete endpoint checks both administrator status and this toggle.
- **`enable_multi_host`**, default `false`: adds configured-host job checks and allows UI export/cancellation propagation. Leave it off for one project. When off, saved domains do not trigger those requests. When enabling it through Settings, a non-empty Domains field is required. Existing non-blank saved domains are enabled by the upgrade migration; an environment-only host list does not enable the feature.
- **`domains`**, default `null`: comma-separated installation URLs used for multi-host requests. Include `http://` or `https://`, for example `https://one.example,https://two.example`, with no trailing slash. The code trims list entries and appends the API route paths. Use this only for participating installations. When the saved value is null, `LANGUAGES_MULTIPLE_DB_HOSTS` is the fallback; a saved empty string does not use that fallback. Turn multi-host off before clearing the field. The setting request enforces presence when enabled, but does not test URL syntax or reachability.

The table also contains an internal `process_running` flag, ID, and timestamps. They are not Settings controls; the controller rejects updates to fields outside the nine listed above. Job gating reads actual queue/batch records rather than relying solely on `process_running`.

## How Translations Are Served

### Database mode

`db_loader=true` is the default for new installations. The initial settings row and column default are true. The upgrade migration changes the default and clears cached loader selection without overwriting existing rows; rollback restores the false default without changing saved preferences.

Laravel's translation loader reads cached database entries for the requested locale/group/namespace. For approved rows it uses `value`; for unapproved rows it uses `old_value`. It does not use the draft value until approval. A newly generated unapproved row can have no old value, so the DB loader supplies no approved text for it. Requesting translation on an approved row does not create an old value, which can also leave it without usable text while unapproved. Vendor namespaces use files when `import_vendor` is off.

DB loading itself writes nothing to `lang/`. With the normal DB workflow, import once, edit, and approve; there are no exported application translation files for a deployment to clobber. **`languages:export-translations-deployment` is unnecessary for DB-loader delivery.** Application translations remain in the database across filesystem deployments. Model translations still need export to their JSON columns in the application database.

The setting is not a global filesystem-write prohibition: Add Language creates a directory; the deployment command and inter-host forced-export endpoint do not pass the model-only flag and can write files even with `db_loader` enabled.

**The database becomes a hard dependency for rendering web requests in DB-loader mode.** Database failures propagate during web loader registration and uncached lookups. The database-exception fallback to Laravel's file loader exists only under `runningInConsole()` during loader registration. It does not cover later console lookups or provide an automatic web fallback during an outage. Cached entries can satisfy individual lookups, but are not a complete substitute for a working database.

### File mode

With `db_loader=false`, Laravel uses its file loader for runtime translation lookup. The package database still stores edits and review state. After approval, export PHP/JSON translations to `lang/`, and model translations to their model columns. Ordinary export requires Approved true, Updated false, and Exported false; forced export ignores only Exported.

Application PHP exports go to `lang/{locale}/{group}.php`, JSON to `lang/{locale}.json`, and vendor overrides under `lang/vendor/{namespace}/`. Exports merge eligible keys into existing content. PHP dotted keys are written as nested arrays: `a.b` becomes `['a' => ['b' => 'text']]`. Matching literal dotted keys from older PHP exports are removed when rewritten, while unrelated entries are retained. JSON keeps literal keys. Use a forced export in file mode to rewrite approved entries already marked exported.

File mode gives you runtime translation files that can be included in a deployment artifact. Those files must stay synchronized with the translation database, and a deployment that replaces them can lose the current exported text until a forced export restores it. DB mode avoids that deployment step but requires database/cache availability for serving translations. File mode does not remove the translation panel's own database dependency.

### Cache and model integration

**`CACHE_DRIVER` must not be `database` in DB-loader mode.** If the application's cache config uses `CACHE_STORE`, the same restriction applies there. Use Redis or Memcached, or a file cache for one server. Configure web processes and queue workers with the same cache store and prefix. Hosts sharing translations should share Redis or Memcached so cache version changes reach every host.

Translation cache entries have no TTL. Keys include a shared version advanced after translation writes commit, including package bulk imports, approvals, exports, and deletes. Targeted invalidation is retained. Application code performing bulk writes that bypass model events must call `Translation::invalidateCacheAfterWrite()` after a successful write so invalidation occurs after commit. Changing records with raw SQL without this hook can leave cached values stale.

Configure model classes in `languages.translatable_models`. Each model must expose its translatable column names, for example `public array $translatable = ['name'];`, and those columns must hold JSON objects keyed by locale. Imports copy existing locale values into Model rows. Export updates the relevant locale inside the existing model column and marks that translation exported. The target model and column must still exist. Approval in the translation panel alone does not update the application's model data.

## Single Project and Multi-Host

### Single-project operation

A single project is the default. Leave `enable_multi_host` off and Domains empty. No inter-host secret is needed for ordinary UI use. Imports, missing-translation creation, approvals, and normal export check local package jobs/batches. Export and cancellation stay local, even if an old domain list remains saved.

### Configure participating hosts

Use multi-host coordination for installations that need coordinated work and exports, typically using the same translation database through `LANGUAGES_DB_CONNECTION`. Coordination does not replicate separate translation databases.

1. Point participating installations at the intended translation database and configure their queue/cache access.
2. Set the same `LANGUAGES_API_SHARED_SECRET` on every participating host. It supplies `languages.api_shared_api_key`; outgoing requests send it as `api_key`.
3. Save the installation URLs in **Settings > Domains**, including the URL scheme.
4. Enable **Enable multi-host coordination**.
5. Keep `LANGUAGES_MAIN_SERVER_DOMAIN` consistent with the main UI installation. Only the installation whose configured `app.url` matches that value registers the panel's web routes; API routes are still registered on enabled installations.

Enabled job gating asks other listed hosts whether work is running. Responses reporting active work block the operation. Unreachable hosts and non-success responses are ignored by this check; it is not a distributed lock or a guarantee that an unavailable host is idle. UI export completion requests forced exports on peers, while cancellation posts to peer cancellation endpoints. Requests skip a URL exactly matching the current request's scheme and host.

### Inter-host API

The package exposes these POST endpoints under `/api`, independently of the panel prefix:

- `/api/languages-has-jobs-running`: returns whether local package jobs or unfinished, uncancelled batches exist.
- `/api/cancelJobs`: cancels local package batches and deletes local package database-queue jobs.
- `/api/languages-force-export`: queues a forced export for every language. This endpoint can write files even in DB-loader mode.
- `/api/languages-get-languages`: returns language records for developer download.
- `/api/languages-get-paginated-translations`: returns translation records in pages of 500 for developer download.

Each requires a non-empty string `api_key` matching the shared secret. **The API fails closed with HTTP 503 when no shared secret is configured.** Request validation happens first, so a missing or invalidly typed `api_key` receives HTTP 422; a non-matching key with a configured secret receives HTTP 401. The public GET `/api/version` only reports the package version and does not use this authentication middleware.

Turning multi-host off stops automatic outgoing coordination requests; it does not unregister or disable these authenticated API endpoints. The developer-download command also uses the API independently of the toggle.

## Background Jobs and Queues

### What runs in the background

The UI queues language/translation imports, missing-translation generation, bulk approvals, exports, and update-and-auto-translate operations in Laravel batches. Missing-translation batches add further jobs as work is discovered. Jobs use `languages.queue_name` (`languageProcessor` by default), and batches use `languages.batch_name` (`languageBatch`). Pending reminders and administrative completion notifications also use the package queue.

Run a worker for this queue when using an asynchronous queue connection:

```bash
php artisan queue:work --queue=languageProcessor
```

With a synchronous queue connection, work runs in the triggering process instead of requiring a worker. CLI import, missing-translation, and export commands call their services synchronously; their queued notifications can still require a worker. The automatic reminder command queues notifications rather than delivering mail itself.

Signed-in users get a batch progress indicator. Page loads recover an already running batch, and a redirect from a newly started action carries its batch ID. Progress polls once per second while the tab is visible, pauses while hidden, and stops when the batch finishes, is cancelled, or disappears. Completion produces a toast. Reload the table to see changed rows; polling does not reload it automatically.

### Why an action may be blocked

Imports, find-missing, exports, bulk approval, update-and-auto-translate, and every single-row mutation (update, approve, request, remove request, restore) refuse to start while a package job or unfinished, uncancelled batch exists. Modal reads and AI draft suggestions remain available because they do not write translations. The normal import/find/export CLI commands use the shared guard too. Multi-host adds the peer checks described above; unreachable peers are ignored.

The running-job check reduces overlapping writes but is not an atomic lock against simultaneous requests. Cancellation, language/account management, and settings changes remain available. Coordinate maintenance with active workers; cancellation cannot interrupt work already executing.

The local guard and cancellation service query the `jobs` and `job_batches` tables on the default database connection. `languages.db_connection` configures package models and migrations but does not redirect every queue-table query. Keep the queue/batch setup consistent with those queries. A non-database queue is not cleared by deleting database `jobs` rows.

### Cancel and maintain batches

Use **Languages > Delete running Batch (Jobs)** to mark unfinished package batches cancelled and delete database `jobs` rows for the package queue. This can also remove queued package notifications. Cancellation does not roll back completed imports/edits/exports or terminate a worker already executing a job. The job handlers do not provide a general mid-job cancellation check. Inspect the result before starting replacement work.

With multi-host enabled, the same button requests cancellation on other configured hosts. It does not provide a per-job picker or a retry-failed-jobs screen.

Run `languages:prune-batches` to remove old finished/cancelled batch records. It does not cancel active work or delete queued/failed jobs. The package does not actively schedule pruning or automatic reminders; arrange their invocation in the host application's scheduler or operational tooling.

## Permissions and Shared Controls

### Administrator and translator access

Administrators can access all language records, translations, translator management, and Settings. Their UI includes language creation/deletion, imports, missing-translation generation, bulk approval/export, cancellation, and the translation approval/request/restore controls.

A non-admin translator's normal UI allows them to:

- List and search assigned languages and open their translations.
- Search, paginate, and use every translation filter on those languages.
- Choose an example language, open Translate, read the available examples, and update text for review.
- Use OpenAI translation when its toggle and source/example conditions are met.
- Read this manual, use their notification controls, switch theme, and log out.

The UI does not show non-admins the bulk language toolbar, approval/request/restore controls, export controls, translator management, or Settings. It has no non-admin account-management screen.

All privileged endpoints enforce administrator authorization on the server. Every per-row translation action resolves its ID through the requested language and checks the translator's language assignments; a foreign row ID returns 403 without changing data. Non-admins can edit permitted translations and request draft suggestions, but cannot approve, request, restore, bulk-update, export, manage accounts, change settings, or run language maintenance. Notification reads, unread changes, and mark-all operations are scoped to the authenticated translator and notifiable type.

### Navigation, theme, and notifications

Navigation includes Languages and Manual for signed-in translators, with Translators and Settings added for admins. Open the compact navigation menu on small screens. The theme button switches light/dark mode and stores the preference in the browser; before a choice is saved it follows the system preference.

The notification button shows the unread count. Open it to read messages, close the panel without marking them read, dismiss one message to mark it read, or use **Mark all as read**. Notifications refresh every five seconds while the tab is visible and pause in hidden tabs. Immediate toasts are separate: success, deleted, info, or warning, with a dismissal button and timer. The theme retains `localStorage["color-theme"]` and updates both the document's `.dark` class and `data-theme` attribute.

## CLI Reference

Run commands as `php artisan ...` from the host Laravel application's directory. These are all eight command signatures in `src/Console/Commands/`. The normal import/find/export commands check running jobs and can return early with `Another Process is running.`; that early return is not a completed operation. Commands use the saved settings unless an exception is specified below.

### languages:import-languages

Signature:

```text
languages:import-languages
```

Imports supported locale directories directly under Laravel's language path, skipping codes already recorded. It does not discover languages solely from `{locale}.json` files or import translation content. Runs synchronously and queues an administrator result notification. Use it for the first import or after adding locale directories.

```bash
php artisan languages:import-languages
```

### languages:import-translations

Signature:

```text
languages:import-translations
```

Imports PHP/JSON sources and configured model translations for existing languages. Respects `import_vendor` and `import_only_from_root_language`. Existing language/shared-identifier pairs are retained; new imports start approved/exported. It reports total existing and newly inserted translations. Use it after introducing new source keys or model values, after importing the language records. It does not update existing translated values from changed files.

```bash
php artisan languages:import-translations
```

### languages:find-missing-translations

Signature:

```text
languages:find-missing-translations
```

Checks translation counts per language and, when counts differ, invokes root-language missing-counterpart creation. Empty languages are represented separately in that check. Uses `app.locale` as the source, or the first language when absent, and can use OpenAI. New rows are unapproved, need translation, and are not exported. It reports inserted counts and queues a root-language administrator notification when that root record exists.

Use it after importing root-language keys or adding languages. **Limitation:** equal row counts can hide different key sets; the CLI then says `Everything up to date.` without checking identifiers. The UI Find Missing Translations action calls the service without this count shortcut.

```bash
php artisan languages:find-missing-translations
```

### languages:export-translations

Signature:

```text
languages:export-translations {--force=}
```

Exports approved, not-updated translations. Ordinarily it only exports rows marked not exported. `--force` is a value-taking option, cast to boolean: use `--force=1` to include already exported rows; omitting it or using `--force=0` retains the ordinary behavior. Force does not bypass approval or the running-job guard.

In file mode it exports PHP/JSON and model content. In DB-loader mode it passes model-only export and skips files. It runs synchronously and queues administrator result notifications; it does not trigger peer exports. The initial candidate count includes file rows even in DB mode, so its reported counts are not a count of files written.

Use it for regular publication or, in file mode, a forced rewrite of files whose database rows are already marked exported.

```bash
php artisan languages:export-translations
php artisan languages:export-translations --force=1
```

### languages:export-translations-deployment

Signature:

```text
languages:export-translations-deployment
```

Synchronously force-exports each language's approved, not-updated translations, including models, ignoring the Exported flag. It takes no `--force` option. It has no running-job guard, no peer-export propagation, and does not pass the DB-loader model-only setting, so it can write files even when `db_loader=true`.

Use it after a file-mode deployment that replaced exported translations. It is unnecessary for DB-loader delivery; omit it from that deployment workflow. It prints a per-language completion message even if there was no eligible content.

```bash
php artisan languages:export-translations-deployment
```

### languages:prune-batches

Signature:

```text
languages:prune-batches
```

Deletes matching `languages.batch_name` rows from `job_batches` on `languages.db_connection` when their finished or cancelled timestamp is older than `languages.prune_batch_hours`, default 24 hours. It does not touch active batches, queue jobs, or failed-job records. There are no command-specific options or completion output.

Use it for retained-batch housekeeping. Schedule it in the host application if recurring cleanup is required; the package's scheduling code is inactive.

```bash
php artisan languages:prune-batches
```

### languages:send-automatic-pending-translations-notification

Signature:

```text
languages:send-automatic-pending-translations-notification
```

This is the actual command name implemented by `SendAutomaticPendingNotifications`; `languages:send-automatic-pending-notifications` is not a registered alias.

When `enable_automatic_pending_notifications` is true, it iterates every translator, including administrators, and their explicit language assignments. For each language with Needs Translation rows, notifications are queued for database and mail delivery. Zero pending rows produce no delivery. When the toggle is false, the command does nothing. It does not require `enable_pending_notifications`, does not use the running-job guard, and prints no success summary.

Use it for recurring reminders with a host-managed schedule, working mail transport, and a package queue worker. No active schedule is registered automatically, and running it again can send another reminder for the same pending work.

```bash
php artisan languages:send-automatic-pending-translations-notification
```

### languages:developer-download

Signature:

```text
languages:developer-download
```

Downloads languages and paginated translations from `languages.main_server_domain`, configured through `LANGUAGES_MAIN_SERVER_DOMAIN`, using `LANGUAGES_API_SHARED_SECRET`. It replaces local language and translation rows, then force-exports the downloaded approved, not-updated content. File mode exports files and models; DB-loader mode exports models only. Settings, translator accounts, and assignments are not downloaded.

Use it only to intentionally replace a development copy with the main server's translation data. **This deletes/replaces local translation work without a confirmation prompt.** There is no local-environment guard, running-job guard, dry-run flag, host argument, or merge mode. The database phase uses a transaction and MySQL/MariaDB `SET FOREIGN_KEY_CHECKS` statements, so it is not portable to SQLite or PostgreSQL as written. Export happens after that transaction commits; an export failure does not undo the downloaded database replacement. Local model targets must exist for model export.

```bash
php artisan languages:developer-download
```

## Troubleshooting

### Jobs do not finish or another process is reported

Confirm that a worker consumes the configured queue, normally `languageProcessor`. Check the package queue's `jobs` records, unfinished `languageBatch` batches, application logs, and failed-job records. Pending notifications can also keep this queue occupied. A worker listening only to the default queue will not process the package queue.

After checking whether work is still executing, use **Delete running Batch (Jobs)** for abandoned work. Pruning old batches is not cancellation. For multi-host installations, check peers and shared secrets; an unavailable peer is ignored during the busy check but export propagation can fail. Review the queue-table connection and non-database-queue limits above.

### A language, key, or source example is missing

Import Languages only recognizes supported locale directories. Add a language explicitly for JSON-only sources. Ensure `lang/` exists before importing; DB mode does not create missing source directories during import. Make sure the root/fallback language records exist and refresh the list after background imports.

Import Translations only visits stored languages, and root-only/vendor settings can exclude sources. Re-import does not overwrite an existing value. Find Missing Translations copies root identifiers, not keys unique to another language, and the CLI's equal-count shortcut can miss differing key sets. Use the UI action in that case. A missing `app.fallback_locale` record prevents the editor mounting; recreate that language.

### Filters or settings appear not to save

Search and filters reload the page with query parameters. Settings fields submit independently on change. Wait for navigation to finish before reloading, and check inline errors when a setting is rejected. Save Domains before enabling multi-host, and disable multi-host before clearing Domains. If controls do nothing, rebuild and republish the package assets, then check for failed JavaScript or network requests. Batch and notification polling intentionally pause in hidden tabs.

### Approved content is not visible or files are unchanged

In file mode, approve edits and export them. Normal exports skip unapproved, updated, or already-exported rows; use `--force=1` only when re-exporting approved, not-updated content is needed. Verify filesystem permissions and PHP/JSON source validity. Malformed existing JSON and JSON encoding failures abort an export rather than silently replacing the file with invalid content.

In DB mode, absence of file writes in the normal workflow is expected. Check the stored approval and old-value state, cache configuration, and shared cache prefix. A false Exported flag does not prevent DB delivery. Model columns still need model export. The deployment command and peer forced-export endpoint can write files despite the DB setting.

### Database, cache, or settings failures

Restore database connectivity for a DB-mode web outage; there is no automatic web fallback to files. `CACHE_DRIVER`/`CACHE_STORE` must use a non-database store, and workers and web processes must share the appropriate cache configuration. Package writes invalidate versioned entries after commit; external bulk writes need the invalidation hook.

If the settings table is absent or has no row, loader selection can choose Laravel's file loader. Package operations that require settings throw `MissingSettingsException` when its row is absent. Restore the saved settings row; the package does not recreate it or replace saved preferences automatically. After repairing settings outside the UI, refresh its cached representation with `Setting::getFreshCached()` in application maintenance code.

### OpenAI or pending email does nothing

Check the relevant toggle, optional OpenAI package and API configuration, selected example, and application logs. OpenAI failures can return source text unchanged. Editor buttons have root/non-root and example-presence conditions; generated text still needs review and approval.

Pending reminders count Needs Translation, not every unapproved row. Check the account's explicit language assignments, mail transport, and queue worker. The manual reminder toggle and automatic-command toggle are independent, and no schedule is installed for the latter. Marking an on-screen notification read does not change translation state.

### Access, routes, and inter-host errors

For a missing UI route, check `LANGUAGES_ENABLED`, the configured route prefix, and the exact match between the main-server value and `app.url`. For a non-admin 403, check assignments and whether the requested screen is admin-only. For a missing row action, check its approval/request/old-value conditions.

Inter-host HTTP 503 means no shared secret is configured after request validation; HTTP 401 means a mismatch, and HTTP 422 can mean a missing or invalid `api_key`. Configure the same secret on each host and use installation URLs with a scheme. The multi-host switch does not disable the API itself.

### Import, model-export, or developer-download exceptions

Check the logged path/error identifier for invalid source files or insert errors. Null source values, namespaces, or groups are not valid missing-translation copy metadata. Application translations use an empty namespace; JSON translations use an empty group. Model exports require an Eloquent model class, an existing record, and an existing JSON translation column. Missing targets raise an exception instead of being silently marked exported.

Developer download requires compatible MySQL/MariaDB SQL, reachable main-server API endpoints, and the matching shared secret. It replaces local records and commits before exporting, so inspect which phase failed before repeating it. Existing local translator/assignment data is not synchronized by the download.
