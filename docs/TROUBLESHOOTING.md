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

## API Sync Fails Across Hosts

Checks:

- Same `LANGUAGES_API_SHARED_SECRET` on all hosts
- Correct `LANGUAGES_MAIN_SERVER_DOMAIN`
- `domains` list in settings uses full scheme (`http://` or `https://`)
- network/firewall allows requests

## Livewire 2/3 Event Issues

This package contains a compatibility trait:

- `src/Livewire/Traits/DispatchesLegacyEvents.php`

If custom components are added, keep event dispatching compatible with both majors.

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
