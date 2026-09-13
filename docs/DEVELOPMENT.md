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
composer audit
npm audit
npm run production
```

## Notes

- Browser acceptance tests use the existing standalone Testbench harness. Run
  `./tests/e2e/seed.sh` followed by `npx playwright test`. See the
  [E2E guide](../tests/e2e/README.md) for fixture isolation, UI selector rules,
  coverage, and reproduced application bugs marked `test.fixme`.

- Testbench is configured in `tests/BaseTestCase.php`.
- Tests force SQLite in-memory and `queue.default=sync` to make setup deterministic.
- Front-end assets are built with Vite (`vite.config.js`).
- Generated assets are in `public/css/app.css` and `public/js/app.js`.
