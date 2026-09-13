# Architecture

## UI and authorization

Controllers in `src/Controllers` render Blade pages in `resources/views`. The package
URL prefix and screen segments still come from `config('languages.*_url')`.
Navigation, search, filters and pagination are full GET requests. Other mutations
are CSRF-protected POST forms followed by redirects, including independent settings
forms. Form requests validate language/account creation, profile/password changes,
login and supported settings fields.

`EnsureTranslator` resolves the package guard's translator. `EnsureAdmin` protects
privileged routes. `BaseController::scopeLanguages()` restricts listings, and every
per-row translation endpoint uses `resolveTranslation()` to verify both assignment
and row ownership. Notification queries use the authenticated notifiable relation.

## Browser modules and JSON endpoints

Vite builds vanilla ES modules. Flowbite utility classes and its CSS plugin remain;
its JavaScript runtime is not loaded. Native dialogs provide modal focus handling.

- `modal.js`: fetches a row's modal data and optional OpenAI suggestion. Suggestions
  never save automatically and edited drafts require explicit replacement.
- `batch-progress.js`: recovers the running batch rendered with the page or tracks
  a new batch ID flashed by a POST action. Stops on completion/cancellation.
- `notifications.js`: polls unread notifications and marks owned messages read.
- `polling.js`: cancels in-flight polls and timers while the tab is hidden.
- `filters.js` and `search.js`: submit ordinary GET forms and per-field settings POSTs.
- `toast.js`: accepts message, type (`SUCCESS`, `DELETED`, `INFO`, `WARNING`) and
  duration from session flash or browser events; displays escaped, dismissible text.

JSON is limited to `batch/progress`, notifications, `{language}/{id}/modal` and
`{language}/{id}/suggest`, beneath the configured prefix and translations segment.
Row updates, approvals, requests and restores use forms. The modal saves its
`translatedValue` textarea by POST and keeps table filters in the action URL.

## Services, jobs and data

Import/export, missing translations and OpenAI services remain independent of the UI.
Controllers dispatch Laravel batches using configured queue and batch names. The
shared `Services/Traits/ChecksForRunningJobs` supports both controllers (session
flash) and console commands (terminal messages). All translation mutations are
subject to this guard; modal reads and draft suggestions do not write data.

Translations are imported from PHP/JSON files or configured models into DB records.
Approved translations are served by the default database loader, or exported in file
mode. Model exports remain available in either mode. Multi-host propagation is opt-in
and the inter-host API contract is unchanged.

Settings cache refreshes update loader selection. Translation caches use a shared
version and targeted invalidation. Bulk writes bypassing model events call
`Translation::invalidateCacheAfterWrite()` so invalidation happens after commit.
