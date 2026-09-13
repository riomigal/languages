# AGENTS.md

This file is for humans and coding agents working in this package.

## Scope

This package provides a Laravel translation UI with controllers, server-rendered Blade, and vanilla JavaScript, testbench tests, and front-end assets built with Vite.

Main documentation entrypoint:

- `docs/README.md`

## Current Tooling

- PHP package manager: Composer
- JS package manager: npm
- Front-end build tool: Vite
- Test runner: PHPUnit (via Orchestra Testbench)

## Key Commands

- Install PHP dependencies: `composer install`
- Update PHP dependencies: `composer update --with-all-dependencies`
- Audit PHP dependencies: `composer audit`
- Install JS dependencies: `npm install`
- Build assets (production): `npm run production`
- Run test suite: `./vendor/bin/phpunit`
- Audit JS dependencies: `npm audit`

## Important Project Rules

- Keep package tests running against SQLite in-memory testbench setup.
- New installs use the DB translation loader; upgrades must preserve saved `db_loader` values.
- Translation cache keys are versioned. Keep targeted invalidation, and call
  `Translation::invalidateCacheAfterWrite()` for bulk translation writes that bypass
  model events so the shared version advances after commit.
- Do not reintroduce `laravel-mix`; this package now builds assets with Vite.
- Tailwind 4 is wired via `@tailwindcss/vite`; there is no `postcss.config.js`.
  Do not reintroduce `postcss`/`autoprefixer`.
- The Vite config is `vite.config.mjs` (ESM); keep the `.mjs` extension.
- Tests use PHPUnit attributes (`#[Test]`), not `/** @test */` doc-comments,
  which PHPUnit 12 removes.
- Keep docs/manual synchronized with code:
  - Update `docs/APPLICATION_MANUAL.md` and related docs for every feature addition, behavior change, or UI update.
- Use full page GET navigation for search, filters and pagination, and POST forms
  with redirects for mutations. Settings save independently per field.
- JSON/fetch is limited to progress, notifications, modal data and AI suggestions.
  Polling must pause in hidden tabs and batch polling must stop on completion.
- Resolve every translation row through BaseController::resolveTranslation();
  privileged actions require the languages.admin middleware.
- If dependencies are changed, re-run:
  - `composer audit`
  - `npm audit`
  - `./vendor/bin/phpunit`
  - `npm run production`

## Where To Start

- Package service providers: `src/LanguagesServiceProvider.php`, `src/LanguagesTranslatorServiceProvider.php`
- UI controllers: `src/Controllers/*`; browser modules: `resources/js/modules/*`
- Test base setup: `tests/BaseTestCase.php`
- Package config: `config/languages.php`
- Build config: `vite.config.mjs`
