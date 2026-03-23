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

## GitHub PR Not Created

Checks:

- `LANGUAGES_GITHUB_PR_ENABLED=true`
- repository format is `owner/repo`
- token has required repository permissions
- export actually produced changes

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

## Composer Blocks Laravel 9 Installation

Symptom:

- Composer reports `laravel/framework ^9.52` is blocked by security advisories.

Notes:

- This block is enforced by Composer audit policy in the host application.
- `riomigal/languages` no longer hard-requires `openai-php/laravel`; OpenAI is optional.

Resolution options:

1. Recommended: upgrade host app to a supported Laravel major without blocked advisories.
2. Temporary workaround in host app: configure Composer audit policy (`ignore` specific advisory IDs or set `block-insecure` to `false`).
