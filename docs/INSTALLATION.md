# Installation

## 1. Require Package

```bash
composer require riomigal/languages
```

Laravel package auto-discovery loads:

- `Riomigal\Languages\LanguagesServiceProvider`
- `Riomigal\Languages\LanguagesTranslatorServiceProvider`

Optional (only for OpenAI-powered translation suggestions):

```bash
composer require openai-php/laravel
```

## 2. Publish Files

```bash
php artisan vendor:publish --tag=languages-config
php artisan vendor:publish --tag=languages-migrations
php artisan vendor:publish --tag=languages-public
```

Optional publishes:

- `php artisan vendor:publish --tag=languages-translations`
- `php artisan vendor:publish --tag=languages-views`

## 3. Run Migrations

```bash
php artisan migrate
```

The package creates language tables and also queue-related tables (`jobs`, `job_batches`, `failed_jobs`) if missing.

## 4. Build Front-End Assets

Inside this package repository, assets are built with Vite:

```bash
npm install
npm run production
```

Published runtime assets are served from:

- `public/vendor/languages/css/app.css`
- `public/vendor/languages/js/app.js`

## 5. Access the UI

Default routes:

- Login: `/translator/login`
- Languages: `/translator/languages`
- Translators: `/translator/translators`
- Settings: `/translator/settings`

The prefix and path segments are configurable in `config/languages.php`.

## 6. Initial Credentials

By default, migration `2023_02_02_205158_create_admin_translator.php` creates:

- Email: `admin@admin.com`
- Password: `aaaaaaaa`

Change this password immediately after first login.

## 7. First-Time Data Import

After login (admin):

1. Go to **Languages**.
2. Run **Import Languages**.
3. Run **Import Translations**.
4. Optionally run **Find Missing Translations**.

Equivalent CLI:

```bash
php artisan languages:import-languages
php artisan languages:import-translations
php artisan languages:find-missing-translations
```
