# Development

## Requirements

- PHP 8.2+ (8.3+ to test against Laravel 13)
- Composer
- Node.js ^20.19 || >=22.12 (required by Vite 8) + npm

## Install

```bash
composer install
npm install
```

## Local Checks

```bash
./vendor/bin/phpunit
./vendor/bin/phpstan analyse --no-progress
composer audit
npm audit
npm run production
node --test tests/js/*.test.mjs
```

## Notes

- Browser acceptance tests use the existing standalone Testbench harness. Run
  `./tests/e2e/seed.sh` followed by `npx playwright test`. See the
  [E2E guide](../tests/e2e/README.md) for fixture isolation, UI selector rules,
  coverage, and the active regression cases. No browser scenarios are skipped.

- Testbench is configured in `tests/BaseTestCase.php`. Its harness excludes the removed UI packages from discovery, so stale local vendor files do not provide a runtime during tests.
- Tests force SQLite in-memory and `queue.default=sync` to make setup deterministic.
- Front-end assets are built with Vite (`vite.config.mjs`).
- Tailwind 4 configuration lives in `resources/css/app.css`: DaisyUI 5 supplies CSS components and light/dark themes, and `@tailwindcss/typography` styles the manual. There is no Tailwind JavaScript config or component JavaScript runtime.
- After the DaisyUI migration, run `npm install` to install the new dependency and regenerate `package-lock.json`, then `npm run production` before running browser checks or publishing assets. The migration edits do not refresh the lockfile or generated assets without the dependency installed.
- Dropdowns retain the existing `data-toggle` handlers and `hidden` state. Their `dropdown-open` class lets those handlers control visibility without depending on focus. The translation editor uses a native dialog with a `modal-box`; outside-click detection measures the box. The theme keeps `localStorage["color-theme"]`, `.dark`, and `data-theme` synchronized.
- Generated assets are in `public/css/app.css` and `public/js/app.js`.
