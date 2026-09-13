# Troubleshooting

## Cannot Login to Translator UI

Checks:

- Route prefix and login URL (`config/languages.php`)
- Translator user exists in `languages_translators`
- Correct guard config (`languages_translator`)

Default first admin (if untouched):

- `admin@admin.com / aaaaaaaa`

## DB Tables Missing During Tests

Use package testbench defaults:

- SQLite in-memory
- `languages.db_connection = testbench`
- `queue.default = sync`

Reference: `tests/BaseTestCase.php`

## Jobs Not Processing

Symptoms:

- `process_running` stays true
- imports/exports do not complete

Checks:

- Queue worker running
- Queue tables exist (`jobs`, `job_batches`, `failed_jobs`)
- queue name matches config (`languages.queue_name`)

## Exports Not Writing Files

Checks:

- File permissions in language directories
- `db_loader` behavior in settings
- export conditions: translation must be approved and not marked updated

PHP exports now expand dotted keys into nested arrays. Existing installations in
file mode can run `php artisan languages:export-translations --force=1` to rewrite
approved translations already marked exported. Matching old flat dotted entries
are removed during export. JSON keys remain literal.

If an export job refers to a deleted translation or target model, it raises a
model-not-found exception instead of failing on null. Restore the required record
or remove the obsolete translation before retrying. Null namespaces/groups are
reported as invalid metadata; application translations use `''` for the namespace
and JSON translations use `''` for the group. Model exports require a model class
and an existing column. Copying missing translations rejects null source text with
a descriptive package exception. Translation updates require an acting translator.

Bulk creation failures log the actual source path (`lang/en/messages.php` or
`lang/vendor/package/en/messages.php`, and `lang/en.json` for JSON), including when
the insertion array uses string keys.

## Settings Row Missing

`Setting::getCached()`, cache refreshes and job-state updates throw
`MissingSettingsException` when the settings table has no row. Restore the saved
row on the configured connection/table, then refresh the settings cache. No default
row is silently inserted. Loader registration uses Laravel's file loader while the
row is absent; this does not change database-outage handling in DB mode.

## OpenAI Keeps the Original Text

The response must be a JSON object containing exactly the requested keys and only
string values. Missing/extra keys, nulls, numbers, booleans, nested values and lists
cause the entire original input to be returned. Missing language records or null
text skip the request. Valid responses preserve the caller's key order, including
empty strings and `"0"`.

## API Sync Fails Across Hosts

Checks:

- Same `LANGUAGES_API_SHARED_SECRET` on all hosts
- Correct `LANGUAGES_MAIN_SERVER_DOMAIN`
- `domains` list in settings uses full scheme (`http://` or `https://`)
- network/firewall allows requests

## UI Controls or Polling Do Not Work

Build and republish `languages-public`, then clear overridden templates that use the
old component UI. Navigation, search and filters use GET page loads; mutations use
POST forms with CSRF tokens. A 419 response requires refreshing the session.
Settings save one field per page reload. Save Domains before enabling multi-host.

Only batch progress, notifications, modal data and AI suggestions use JSON requests.
Polling pauses in hidden tabs; batch polling ends on completion or cancellation.
The modal's AI result is a draft until explicitly saved. When a draft has been edited,
use the separate suggestion preview to replace it.

## NPM Audit / Composer Audit Failures

Run:

```bash
composer update --with-all-dependencies
npm install
npm audit fix
```

Then verify:

```bash
composer audit
npm audit
./vendor/bin/phpunit
```

## Composer Blocks Laravel 9, 10 or 11

Symptom:

- Composer reports `laravel/framework` is blocked by security advisories.

Notes:

- Every released version of Laravel 9, 10 and 11 is affected by unresolved advisories.
- `riomigal/languages` therefore requires `^12.0 || ^13.0`. Laravel 9/10/11 are no longer supported.
- `riomigal/languages` does not hard-require `openai-php/laravel`; OpenAI is optional.

Resolution:

- Upgrade the host app to Laravel 12 or 13.
