# Configuration

Main config file:

- `config/languages.php`

## Environment Variables

### Core

- `LANGUAGES_ENABLED` (default: `true`)
- `LANGUAGES_MAIN_SERVER_DOMAIN` (default: `config('app.url')`)
- `LANGUAGES_DB_CONNECTION` (default: `config('database.default')`)

### Multi-Host / API Sync

- `LANGUAGES_API_SHARED_SECRET`
- `LANGUAGES_MULTIPLE_DB_HOSTS` (comma-separated host list)

### GitHub Pull Request Integration

- `LANGUAGES_GITHUB_PR_ENABLED`
- `LANGUAGES_GITHUB_PR_REPOSITORY` (format: `owner/repo`)
- `LANGUAGES_GITHUB_PR_BASE_BRANCH` (default: `main`)
- `LANGUAGES_GITHUB_PR_BRANCH_PREFIX` (default: `translations/export-`)
- `LANGUAGES_GITHUB_PR_TOKEN`
- `LANGUAGES_GITHUB_PR_LANG_PATH` (default: `lang`)
- `LANGUAGES_GITHUB_PR_GIT_USER_NAME`
- `LANGUAGES_GITHUB_PR_GIT_USER_EMAIL`

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

## Settings Managed in UI

The **Settings** page updates the `languages_settings` row:

- `domains`
- `db_loader`
- `import_vendor`
- `enable_pending_notifications`
- `enable_automatic_pending_notifications`
- `enable_open_ai_translations`
- `import_only_from_root_language`
- `allow_deleting_languages`

## Recommended Production Setup

1. Set a dedicated `LANGUAGES_DB_CONNECTION` if needed.
2. Configure queue workers for package jobs.
3. Configure `LANGUAGES_API_SHARED_SECRET` for multi-host sync.
4. Rotate default admin credentials.
5. Enable GitHub PR integration only with properly scoped tokens.
