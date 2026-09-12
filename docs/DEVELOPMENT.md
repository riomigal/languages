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

- Testbench is configured in `tests/BaseTestCase.php`.
- Tests force SQLite in-memory and `queue.default=sync` to make setup deterministic.
- Front-end assets are built with Vite (`vite.config.js`).
- Generated assets are in `public/css/app.css` and `public/js/app.js`.
