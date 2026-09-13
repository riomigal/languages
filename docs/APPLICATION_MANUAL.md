# Application Manual

This manual is for package users working in the translation panel.

Default manual URL:

- `/translator/manual`

## Getting Started

Default login URL:

- `/translator/login`

Login uses the package guard `languages_translator`.

Role summary:

- **Admin**
  - Full access to Languages, Translations, Translators, and Settings.
- **Translator**
  - Access to assigned languages and translation operations only.

## Languages

Use this section to manage language records and run bulk language/translation operations.

### Main Actions

- Add language
- Import languages from filesystem
- Import translations from filesystem
- Find missing translations
- Approve translations across all languages
- Export translations for all languages
- Delete active jobs and batches

### Recommended Workflow

1. Import languages from filesystem.
2. Import translations.
3. Find missing translations.
4. Review pending translations and approve.
5. Export after approvals.

### Notes

- If `allow_deleting_languages` is disabled, delete options are hidden.
- Export behavior changes when `db_loader` is enabled.
- Batch operations are blocked while a local package job or batch is running.
  With multi-host coordination enabled, configured hosts are checked too.

## Translators

Use this section to manage translator accounts and language access.

### Main Actions

- Create translator
- Update translator profile
- Reset translator password
- Assign languages
- Toggle admin access
- Send pending-translation notifications
- Delete translator accounts (except protected first admin account)

### Permission Model

- Admins can manage all translators and all languages.
- Non-admin translators can only work with assigned languages.

### Recommended Workflow

1. Create translator account.
2. Assign at least one language.
3. Validate access by role.
4. Use notifications for pending work when needed.

## Settings

Use this section to configure package behavior for imports, exports, notifications, and integrations.

### Main Controls

- Enable multi-host coordination (`enable_multi_host`, off by default on new installs)
- Shared domains (`domains`, required only when multi-host coordination is enabled)
- Load translations from DB (`db_loader`, on by default on new installs)
- Import vendor translations
- Pending translation notifications
- Automatic pending notifications
- OpenAI translation support
- Import only from root language
- Allow deleting languages

### Recommended Defaults

- Keep `db_loader` enabled to serve translations from the database without exporting language files.
- Enable pending notifications only when translators actively use email alerts.
- Leave multi-host coordination off for a single project; shared domains can be empty.

### Database and File Loading

New installs enable **Load translations from DB** (`db_loader`). The initial settings
row and the column default are `true`. The upgrade migration changes only the column
default, leaving every existing row's saved value untouched. Rolling it back restores
the `false` default without changing saved values.

In DB mode, import translations once and approve changes as usual. Runtime translations
come from the database through a cache. Exports skip `lang/` files, so a deployment
cannot overwrite exported translations; model translation exports still run. File mode
remains supported: disable the toggle when you need exported `lang/` files for version
control or a static deployment, then export approved translations.

**The database is a hard dependency for rendering web requests in DB mode.**
`LanguagesTranslatorServiceProvider::registerLoader()` catches database failures and
falls back to Laravel's file loader only when `runningInConsole()` is true, during
loader registration. Web requests have no equivalent fallback and database failures
propagate. Cached entries may satisfy individual lookups, but cache misses need the
database. The console guard does not catch failures during later translation lookups.
This behavior is preserved; file translations may be absent or outdated in DB mode.

**`CACHE_DRIVER` must not be `database` in DB mode.** Laravel 12/13 applications
normally select the store with `CACHE_STORE`; the same rule applies there. Caching
database rows in the database to avoid reading database rows defeats the point.
Use `redis` or `memcached`; `file` is acceptable on one server. Web processes and queue
workers must use the same cache store and prefix. Multiple hosts sharing translations
should use a shared Redis or Memcached cache so version changes reach every host.

Translation cache entries have no TTL. Their keys include a shared, monotonically
increasing version that advances after translation writes commit, including package
bulk imports, approvals and deletes. Targeted invalidation remains in place, while
each version bump also makes entries missed by targeted invalidation unreachable.

### Single Project and Multiple Hosts

New installs work as a single project by default. With **Enable multi-host
coordination** off, imports, exports and approvals check only local jobs and batches.
Exports and job cancellation also stay local. No shared-host requests are sent,
even if you keep previously configured domains in Settings.

To use multiple hosts sharing the translation system:

1. Save valid package installation URLs in **Domains**, separated by commas and
   including `http://` or `https://`.
2. Configure the same `LANGUAGES_API_SHARED_SECRET` on each participating host.
3. Turn on **Enable multi-host coordination**.

Enabled coordination checks other hosts for running jobs, propagates exports and
cancels jobs on those hosts. Unreachable hosts are ignored during running-job checks.
Turn the toggle off before clearing the domains field. Existing API endpoints remain
available with the same authentication and behavior.

On upgrade, existing settings with non-empty domains are automatically enabled so
exports continue to propagate. Settings with null, empty or space-only domains stay
disabled. A host list provided only through `LANGUAGES_MULTIPLE_DB_HOSTS` does not
enable the toggle; save those URLs in Domains and enable coordination if needed.

### OpenAI Translation

- Optional feature (requires `openai-php/laravel` and valid API configuration).
- Used in translation modal for automatic translation suggestions.

## Daily Operations

### Admin Routine

1. Check pending translations.
2. Approve validated entries.
3. Export updates.
4. Confirm no running batches remain.

### Translator Routine

1. Open assigned language.
2. Filter pending/requested rows.
3. Update translations and submit for review.

## Troubleshooting

- “A process is running in the background”: clear running jobs/batches, or start queue worker.
- Missing new language in UI: run import languages and refresh page.
- Export not writing files: this is expected with `db_loader` enabled; in file mode, verify filesystem permissions and package export settings.
- Database outage in DB mode: restore database connectivity; web requests do not automatically fall back to language files.
