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
- Batch operations are blocked while another process is running.

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

- Shared domains (`domain_urls`) for multi-app sync checks
- Load translations from DB (`db_loader`)
- Import vendor translations
- Pending translation notifications
- Automatic pending notifications
- OpenAI translation support
- Import only from root language
- Allow deleting languages

### Recommended Defaults

- Keep `db_loader` enabled only when runtime DB translation loading is required.
- Enable pending notifications only when translators actively use email alerts.
- Set shared domains only for valid reachable package installations.

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
- Export not writing files: verify filesystem permissions and package export settings.
