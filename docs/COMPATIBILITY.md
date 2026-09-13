# Compatibility

## Laravel

The package Composer constraints currently allow:

- `php`: `^8.2` (the constraint admits PHP 8.2-8.5; CI currently verifies 8.3 only)
- `illuminate/support`: `^12.0 || ^13.0`
- `orchestra/testbench`: `^10.0 || ^11.0`

Laravel 9, 10 and 11 were dropped because every released version of those
majors is blocked by unresolved Composer security advisories. `config.platform.php`
is pinned to `8.2.0` so the lock file always resolves at the declared floor.
Testing against Laravel 13 needs PHP 8.3+, because `orchestra/testbench ^11`
requires it.

## Livewire

- `livewire/livewire`: `^3.0`

Livewire 2 support was dropped: it never supported Laravel 12, so the
`^2.12` branch was unreachable under the current constraints.

### Event Compatibility

Components use Livewire 3 `dispatch()` calls with named event parameters.
The legacy event shim and its unreachable Livewire 2 fallback branches have
been removed. Toast messages, batch progress and modal events retain their
existing names and payloads.

## Build Tooling

Asset pipeline is Vite-based:

- `vite.config.mjs` (`.mjs` so Vite 8 loads it as ESM)
- npm scripts in `package.json`

Tailwind 4 is wired through `@tailwindcss/vite`. There is no `postcss.config.js`:
`postcss` and `autoprefixer` were removed, since Tailwind 4 handles vendor
prefixing via Lightning CSS.

`laravel-mix` is intentionally not used.

## Optional Integrations

- OpenAI translation support is optional and only enabled when `openai-php/laravel` is installed.
