# Documentation Index

- [Installation](INSTALLATION.md)
- [Configuration](CONFIGURATION.md)
- [Application Manual](APPLICATION_MANUAL.md)
- [CLI Commands](CLI_COMMANDS.md)
- [API Reference](API_REFERENCE.md)
- [Architecture](ARCHITECTURE.md)
- [Development](DEVELOPMENT.md)
- [Compatibility](COMPATIBILITY.md)
- [Troubleshooting](TROUBLESHOOTING.md)
- [Agent Guide](../AGENTS.md)

## Quick Start

1. Install the package and migrate:
   - `composer require riomigal/languages`
   - `php artisan vendor:publish --tag=languages-config`
   - `php artisan vendor:publish --tag=languages-migrations`
   - `php artisan migrate`
2. Build/publish package assets:
   - `npm install`
   - `npm run production`
   - `php artisan vendor:publish --tag=languages-public`
3. Login at `/translator/login` (or your configured prefix).
4. Change the default admin password immediately.
5. Import languages/translations from the UI or CLI.
6. Open the in-app manual at `/translator/manual`.
