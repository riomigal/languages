# AGENTS.md

This file is for humans and coding agents working in this package.

## Scope

This package provides a Laravel translation UI with Livewire components, testbench tests, and front-end assets built with Vite.

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
- Do not reintroduce `laravel-mix`; this package now builds assets with Vite.
- Tailwind 4 is wired via `@tailwindcss/vite`; there is no `postcss.config.js`.
  Do not reintroduce `postcss`/`autoprefixer`.
- The Vite config is `vite.config.mjs` (ESM); keep the `.mjs` extension.
- Tests use PHPUnit attributes (`#[Test]`), not `/** @test */` doc-comments,
  which PHPUnit 12 removes.
- Keep docs/manual synchronized with code:
  - Update `docs/APPLICATION_MANUAL.md` and related docs for every feature addition, behavior change, or UI update.
- Livewire 2 is no longer supported (`livewire/livewire: ^3.0`); the legacy
  event shim trait is vestigial and kept only until the Livewire layer is removed.
- If dependencies are changed, re-run:
  - `composer audit`
  - `npm audit`
  - `./vendor/bin/phpunit`
  - `npm run production`

## Where To Start

- Package service providers: `src/LanguagesServiceProvider.php`, `src/LanguagesTranslatorServiceProvider.php`
- Livewire components: `src/Livewire/*`
- Test base setup: `tests/BaseTestCase.php`
- Package config: `config/languages.php`
- Build config: `vite.config.js`
