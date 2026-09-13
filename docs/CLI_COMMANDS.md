# CLI Commands

This reference matches the eight commands registered from `src/Console/Commands/`. The complete in-app guide, including the same CLI reference, is `docs/APPLICATION_MANUAL.md`, rendered at `languages.manual` (normally `/translator/manual`). Run commands from the host Laravel application's directory with `php artisan`.

## Execution and Settings

`languages:import-languages`, `languages:import-translations`, `languages:find-missing-translations`, and `languages:export-translations` run their services synchronously. They first check local package jobs and unfinished, uncancelled batches, and configured peers when `enable_multi_host` is enabled. If busy, they print `Another Process is running.` and return without doing the requested work. That early return is not a successful import/export. These commands maintain the internal `process_running` setting, but the guard checks queue/batch records.

The local guard queries `jobs` for `languages.queue_name` (default `languageProcessor`) and `job_batches` for `languages.batch_name` (default `languageBatch`) on the default database connection. Keep that connection consistent with the application's queue/batch setup. Unreachable or non-success peer responses are ignored during the busy check.

Administrator result notifications and pending reminders are queued. With an asynchronous queue connection, run a worker for the package queue:

```bash
php artisan queue:work --queue=languageProcessor
```

New installations use `db_loader=true`. Normal export and developer download pass model-only export in this mode, so they do not write PHP/JSON translation files. Model translations still require export to the application's JSON columns. File mode (`db_loader=false`) exports files and models. The deployment command is an exception: it does not pass the model-only flag.

DB-loader web rendering requires the database; the database-error file-loader fallback only applies during console loader registration under `runningInConsole()`, not later lookups. `CACHE_DRIVER` must not be `database` in DB mode; the same applies to `CACHE_STORE` when used by the host application. Use a non-database cache shared appropriately by web processes, workers, and participating hosts.

The signatures below list all command-specific arguments/options. Only `languages:export-translations` declares a command-specific option; the other seven commands have none.

## languages:import-languages

Signature:

```text
languages:import-languages
```

Imports supported locale directory names directly under Laravel's language path, normally `lang/`, and skips codes already in the database. It does not infer languages from `{locale}.json` files alone, import vendor locale directories as top-level languages, or import translation text. The language path must exist.

Reports existing names and newly imported languages, or `Nothing imported.` It queues administrator result notifications. Use it for initial setup or after adding supported locale directories. For JSON-only sources, create the language through the Languages screen or provide its locale directory first.

```bash
php artisan languages:import-languages
```

## languages:import-translations

Signature:

```text
languages:import-translations
```

Imports PHP/JSON sources for existing language records, plus configured Eloquent model translations from `languages.translatable_models`. PHP files must return arrays, JSON must decode to arrays, and nested keys are flattened for database storage.

- `import_vendor` enables registered vendor namespace imports, checking published overrides before package sources.
- `import_only_from_root_language` limits the language set to `config('app.locale')`, including vendor and model passes.
- Existing language/shared-identifier pairs are skipped. Changing an existing source value does not replace its saved database value.
- New source imports start approved, not updated, and exported; empty/falsy values are marked as needing translation.

Reports existing and newly inserted translation counts. The CLI's administrator import-notification call is inactive; this differs from the UI import batch. Use it after importing languages and whenever new source keys or model locale values should be added.

```bash
php artisan languages:import-translations
```

## languages:find-missing-translations

Signature:

```text
languages:find-missing-translations
```

Compares per-language translation counts, with a separate sentinel for languages having no rows. When counts differ, it invokes missing-counterpart creation using the language matching `app.locale`, or the first language if that record is absent. It copies root-language identifiers into other languages where missing, leaving existing counterparts unchanged. It does not scan application code, union all language key sets, or fill keys absent from the root itself.

With OpenAI enabled and configured, generated values can be translated automatically; otherwise source text is retained. Created rows are unapproved, need translation, are not updated, and are not exported. The command reports inserted counts and queues an administrator notification for the configured root language when that record exists.

Use it after adding languages or importing root-language keys. **Count-check limitation:** equal counts can conceal different identifiers. The command then prints `Everything up to date.` without comparing keys. The UI Find Missing Translations action does not use this count shortcut.

```bash
php artisan languages:find-missing-translations
```

## languages:export-translations

Signature:

```text
languages:export-translations {--force=}
```

Exports approved rows with `updated_translation=false`. By default, only rows with `exported=false` are exported. The option takes a value and is cast to boolean:

- Omit `--force`, or use `--force=0`, for ordinary export.
- Use `--force=1` to include rows already marked exported.

Force does not bypass approval, the Updated flag, or the running-job guard. It is not a standalone boolean switch.

In file mode, exports write eligible PHP/JSON entries under `lang/` and eligible model values into the application's model JSON columns. Existing file entries are merged. PHP dotted keys are written as nested arrays, removing matching legacy literal dotted keys; JSON keys remain literal. Rows are marked exported after successful writes. Malformed existing JSON and failed JSON encoding raise export errors rather than silently truncating content.

In DB mode, export is model-only. The initial eligibility/count query still includes PHP/JSON rows, so a candidate count is not a count of written files. The command reports results and queues administrator notifications. It does not request exports on other hosts; UI export batches perform that propagation when multi-host is enabled.

Use ordinary export after approval. Use force in file mode when approved files were replaced or need rewriting, or to re-export approved model content in either mode.

```bash
php artisan languages:export-translations
php artisan languages:export-translations --force=1
```

## languages:export-translations-deployment

Signature:

```text
languages:export-translations-deployment
```

Synchronously force-exports each language's approved, not-updated translations, including models, regardless of the Exported flag. It prints a completion line for every language, even if there are no eligible rows. It has no `--force` option, running-job guard, or inter-host export propagation.

**This command does not honor `db_loader` as a model-only export selection.** It can write PHP/JSON files even when DB loading is enabled. Use it after a file-mode deployment replaces exported translation files. Run it only when overlapping bulk work has been ruled out.

**It is unnecessary for DB-loader delivery.** DB mode serves application translations from the database without routine `lang/` exports, so a deployment cannot clobber those translations by replacing language files. Omit this command from the DB-mode deployment workflow; use the normal export command for model translations.

```bash
php artisan languages:export-translations-deployment
```

## languages:prune-batches

Signature:

```text
languages:prune-batches
```

Deletes rows from `job_batches` on `languages.db_connection` whose name equals `languages.batch_name` and whose `finished_at` or `cancelled_at` timestamp is older than `languages.prune_batch_hours`, default 24 hours.

It does not cancel active batches, delete queue jobs, prune failed-job records, check for running work, or print a completion summary. It has no retention-hours option; change the config value to alter retention. Use it for recurring batch-record housekeeping. The package does not register an active schedule for it.

```bash
php artisan languages:prune-batches
```

## languages:send-automatic-pending-translations-notification

Signature:

```text
languages:send-automatic-pending-translations-notification
```

This is the real signature of `SendAutomaticPendingNotifications`. There is no `languages:send-automatic-pending-notifications` alias.

When `enable_automatic_pending_notifications=true`, iterates every translator, including administrators, and each explicitly assigned language. A language with `needs_translation=true` rows produces queued database and mail notifications for that translator. Languages with zero requested rows produce no delivery. Administrators are not automatically notified for unassigned languages merely because their UI access covers all languages.

When the toggle is false, the command does nothing. It does not consult `enable_pending_notifications`, check for running work, or print a result summary. Unapproved rows whose Needs Translation flag is false do not count as pending reminders.

Use it for periodic reminders with the application's mail transport, queue worker, and a host-managed schedule. The package's scheduling code is inactive. Repeated runs can send repeated reminders for the same work.

```bash
php artisan languages:send-automatic-pending-translations-notification
```

## languages:developer-download

Signature:

```text
languages:developer-download
```

Downloads from `languages.main_server_domain`, normally set with `LANGUAGES_MAIN_SERVER_DOMAIN`. Requests send `LANGUAGES_API_SHARED_SECRET` as `api_key`; the source host must have the same secret. The source API fails closed with HTTP 503 when no secret is configured, after request validation. A wrong secret receives 401; a missing/non-string request key can receive 422. Developer download is independent of `enable_multi_host`.

The command performs these steps:

1. Disables foreign-key checks and starts a transaction on `languages.db_connection`.
2. Fetches language records, deletes the local language rows, and inserts the downloaded records.
3. Fetches paginated translations, deletes local translation rows, and inserts all downloaded pages, invalidating translation caches after commit.
4. Commits the database replacement and restores foreign-key checks.
5. Force-exports approved, not-updated translations for every downloaded language. Local `db_loader=true` selects model-only export; file mode exports files and models.

**Destructive local replacement:** use this only when intentionally replacing a development translation database. There is no confirmation prompt, merge mode, dry run, local-environment restriction, running-job guard, or host argument. Back up local work that must be retained. Settings, translator accounts, and language assignments are not downloaded; those local records are not synchronized to the source installation.

The database phase uses MySQL/MariaDB-specific `SET FOREIGN_KEY_CHECKS` statements and is not portable to SQLite or PostgreSQL as written. Its transaction does not cover file/model exports: export runs after commit, so export failure does not undo downloaded records. Model export also requires corresponding local model records and columns.

```bash
php artisan languages:developer-download
```

## Initial Load and Scheduled Operations

An initial source import typically runs in this order:

```bash
php artisan languages:import-languages
php artisan languages:import-translations
php artisan languages:find-missing-translations
```

Complete review and approval in the panel. File mode then needs `languages:export-translations`; DB mode needs export only for application model columns.

For ongoing housekeeping, arrange invocation of `languages:prune-batches` and, if desired, `languages:send-automatic-pending-translations-notification` in the host application's scheduler or operational tooling. Neither setting nor command registers a schedule automatically. Pending reminders also require the automatic notification toggle and a functioning queue/mail configuration.

For cancelled or stuck work, use **Languages > Delete running Batch (Jobs)** rather than expecting pruning to stop it. That action cancels matching unfinished batches and deletes database queue rows. It does not terminate executing worker processes or roll back completed changes.
