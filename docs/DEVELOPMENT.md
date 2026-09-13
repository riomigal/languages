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
- Generated assets are in `public/css/app.css` and `public/js/app.js`.
