# Configuration

Main config file:

- `config/languages.php`

## Environment Variables

### Core

- `LANGUAGES_ENABLED` (default: `true`)
- `LANGUAGES_MAIN_SERVER_DOMAIN` (default: `config('app.url')`)
- `LANGUAGES_DB_CONNECTION` (default: `config('database.default')`)

### Multi-Host / API Sync

Single-project installs need no shared hosts or API secret. The Settings toggle
`enable_multi_host` defaults to `false` on new installs.

- `LANGUAGES_API_SHARED_SECRET`
- `LANGUAGES_MULTIPLE_DB_HOSTS` (comma-separated host list)

To coordinate multiple installations, save the shared URLs in **Settings > Domains**,
including `http://` or `https://`, and turn on **Enable multi-host coordination**.
Configure the same `LANGUAGES_API_SHARED_SECRET` on the participating hosts.
The environment host list remains a fallback when the saved `domains` value is `null`;
it does not enable multi-host coordination by itself.

When the toggle is off, job gating checks only local `jobs` and `job_batches`.
Job checks, exports and job cancellation send no requests to shared hosts, even if
domains remain saved. API endpoints remain registered with their existing contracts.

The upgrade migration enables multi-host for existing settings where `domains` is
neither `NULL` nor empty after `TRIM(domains)`, preserving configured installations.
Rows with null, empty or space-only domains stay disabled. The migration also clears
cached settings so the backfilled value takes effect.

### OpenAI

OpenAI integration is optional.
Install `openai-php/laravel` only if you want automatic translation suggestions.

In `config/openai.php`:

- `OPENAI_API_KEY`
- `OPENAI_ORGANIZATION`
- `OPENAI_REQUEST_TIMEOUT` (default: `30`)

## Important Config Keys

### Routes

- `prefix` (default: `translator`)
- `languages_url`, `translations_url`, `translators_url`, `settings_url`, `login_url`

### Tables

- `table_languages` (default: `languages_languages`)
- `table_translations` (default: `languages_translations`)
- `table_translators` (default: `languages_translators`)
- `table_settings` (default: `languages_settings`)
- `table_translator_language` (default: `languages_language`)

### Queue

- `queue_name` (default: `languageProcessor`)
- `batch_name` (default: `languageBatch`)
- `prune_batch_hours` (default: `24`)

### Auth

- `translator_guard` (default: `languages_translator`)
- `auth_guard` (default: `auth_translator`)

### Translation Behavior

- `translatable_models` (array of model classes using JSON translatable fields)
- `open_ai_model`
- `max_open_ai_missing_trans`
- `cache_key` (default: `languages_cache`; scopes settings, translation entries and their shared version)

### DB Loader and Cache

`db_loader` defaults to `true` for new installations, including the initial settings
row. The upgrade migration changes the column default only; it never updates existing
rows or overrides a saved `true` or `false`. Its rollback restores the `false` default
without modifying existing rows. Laravel 12/13 support this schema change natively;
no `doctrine/dbal` dependency is added. The migration also clears the cached loader
selection, which console startup may have set to file mode before the settings table
existed on a fresh install.

DB mode loads cached translations from the database and skips `lang/` file exports.
Model translation exports continue. File mode remains available by disabling
`db_loader` in Settings and exporting approved translations.

The database is a hard dependency for rendering web requests in DB mode.
`LanguagesTranslatorServiceProvider::registerLoader()` falls back to Laravel's file
loader on a database exception only during console registration (`runningInConsole()`).
Web requests go through `loadTranslationsArray()` without that exception guard, and
uncached translations query the database directly. Database failures propagate; there
is no automatic web fallback. Failures in translation lookups after console registration
are also outside that guard. This preserves existing behavior because DB-mode language
files may be missing or stale.

Set `CACHE_STORE=redis` or `CACHE_STORE=memcached` (or `CACHE_DRIVER` if that is the
environment variable your application's cache configuration uses). `file` is acceptable
on a single server. Do not use `database`: caching DB rows in the DB to avoid reading DB
rows defeats the purpose. Queue workers and web processes must share the same store and
prefix; hosts sharing translations should use the same Redis or Memcached cache.

Translation entries are cached forever under versioned keys. The shared version lives
at `config('languages.cache_key') . ':version'`. Initialization and version bumps use a
cache lock, including with the file store. Package writes advance it after commit;
reads inside transactions bypass the shared cache so uncommitted values cannot leak
into it. Existing targeted invalidation uses the same key builder as cache reads.
Older versions become unreachable and remain stored until cache eviction or clearing.

Custom bulk writes, raw SQL and writes with model events disabled must call
`Translation::invalidateCacheAfterWrite()` on the configured package connection after
the write. This schedules the version bump after commit. Normal `Translation` model
saves/deletes and all package bulk write paths already do this.

## Settings Managed in UI

The **Settings** page updates the `languages_settings` row:

- `domains`
- `enable_multi_host` (default: `false`; requires non-empty `domains` when enabled)
- `db_loader` (default: `true` on new installs; existing values are preserved)
- `import_vendor`
- `enable_pending_notifications`
- `enable_automatic_pending_notifications`
- `enable_open_ai_translations`
- `import_only_from_root_language`
- `allow_deleting_languages`

## Recommended Production Setup

1. Set a dedicated `LANGUAGES_DB_CONNECTION` if needed.
2. Configure queue workers for package jobs.
3. For multi-host sync, configure shared domains and `LANGUAGES_API_SHARED_SECRET`, then enable multi-host coordination in Settings.
4. Rotate default admin credentials.

Each Settings control submits and validates only its own field using an ordinary
POST and redirect. JavaScript submits on change; a Save button works without it.
Enabling multi-host validates against saved Domains, so save Domains first.
Invalid Domains do not prevent changes to unrelated settings.
