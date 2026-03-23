# Compatibility

## Laravel

The package Composer constraints currently allow:

- `php`: `^8.1`
- `illuminate/support`: `^9.0 || ^10.0 || ^11.0 || ^12.0`
- `orchestra/testbench`: `^7.0 || ^8.0 || ^9.0 || ^10.0 || ^11.0`

## Livewire

The package supports both major lines declared in Composer:

- `livewire/livewire`: `^2.12 || ^3.0`

### Event Compatibility

Livewire event API changed between v2 and v3:

- v2 uses `emit(...)`
- v3 uses `dispatch(...)`

This package keeps compatibility through a shim trait:

- `src/Livewire/Traits/DispatchesLegacyEvents.php`

It dispatches events via:

- `dispatch(...)` when running Livewire 3
- `parent::emit(...)` fallback for Livewire 2

## Build Tooling

Asset pipeline is Vite-based:

- `vite.config.js`
- npm scripts in `package.json`

`laravel-mix` is intentionally not used.

## Optional Integrations

- OpenAI translation support is optional and only enabled when `openai-php/laravel` is installed.
