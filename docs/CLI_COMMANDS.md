# CLI Commands

## Core Commands

### `languages:import-languages`

Imports languages from filesystem directories under `lang/`.

### `languages:import-translations`

Imports translation files into database records.

### `languages:find-missing-translations`

Creates missing translation rows from existing language sets.

### `languages:export-translations`

Exports approved translations from DB to files.

Options:

- `--force=1` export regardless of `exported` flag
- `--create-pr` create GitHub PR if integration is configured

### `languages:export-translations-deployment`

Force-exports all languages, intended for post-deploy refresh.

### `languages:prune-batches`

Deletes old finished/cancelled language batches from `job_batches` using `prune_batch_hours`.

### `languages:send-automatic-pending-translations-notification`

Sends pending translation notifications when feature is enabled.

### `languages:developer-download`

Developer sync command:

- Downloads languages/translations from configured main server
- Replaces local DB language/translation content
- Exports downloaded content to local language files

## Recommended Usage

### Initial Load

```bash
php artisan languages:import-languages
php artisan languages:import-translations
php artisan languages:find-missing-translations
```

### Regular Export

```bash
php artisan languages:export-translations
```

### Export + Pull Request

```bash
php artisan languages:export-translations --create-pr
```

### Scheduled Maintenance

```bash
php artisan languages:prune-batches
php artisan languages:send-automatic-pending-translations-notification
```
