# Architecture

## High-Level Components

- **Service Providers**
  - `LanguagesServiceProvider`
  - `LanguagesTranslatorServiceProvider`
- **Livewire UI**
  - `Languages`, `Translations`, `Translators`, `Settings`, `Login`
- **Domain Models**
  - `Language`, `Translation`, `Translator`, `Setting`
- **Services**
  - Import/export/missing translation workflows
  - OpenAI translation support
- **Jobs & Batches**
  - Async operations for imports, exports, and approvals

## Data Flow

1. Filesystem language files are imported into DB.
2. Translators edit/approve translations in UI.
3. Exports write approved DB values back to filesystem.
4. Optional multi-host sync runs after exports.

## Queue Usage

- Package jobs are dispatched to configured queue/batch names.
- Batch progress is surfaced to UI (`BatchExecution` component).
- Queue and batch tables must exist in runtime DB.

## Caching

- Settings are cached using key prefix `languages_cache`.
- DB loader behavior also caches a settings-dependent switch.
