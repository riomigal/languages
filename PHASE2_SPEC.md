# Phase 2 Spec - Remove Livewire, Alpine and Flowbite

Status: DRAFT, awaiting approval. No code written yet.

Target package: `riomigal/languages` (this package only; the host Laravel app at
`package-languages/` is a dev harness and must not be modified).

## 1. Goal

Remove the Livewire runtime entirely and replace the UI layer with
server-rendered Blade + plain vanilla JavaScript. Replace Flowbite with DaisyUI
(pure CSS, no JS runtime). Drop Alpine.

After this work the package must have **zero** front-end runtime dependencies
other than DaisyUI's stylesheet, and **no** PHP dependency on Livewire.

## 2. Non-goals

- Not a visual redesign. DaisyUI replaces Flowbite class-for-class; the screens
  keep their existing layout and information architecture.
- **But this is not a like-for-like port.** Sections 5a-5e and 6/7 define
  deliberate behavior changes the owner has asked for: the GitHub PR feature
  goes, multi-host becomes opt-in, the DB loader becomes the default, the
  translations table gets indexes, the manual gets completed, and the
  authorization holes get closed rather than faithfully reproduced.
- No change to `src/Services`, `src/Jobs`, `src/Models`, `src/Console`,
  `src/Notifications` business logic. Those are already UI-agnostic.
- No change to the existing `routes/api.php` inter-host API contract. Other
  installations call those endpoints; changing them breaks multi-host export.

## 3. Architecture

Livewire's server-driven reactivity is replaced by two mechanisms, not one SPA:

1. **Full page loads** for navigation, filtering, searching and pagination.
   All filter state already lives in the query string (`$queryString` on the
   components), so this is a direct translation, not a redesign.
2. **`fetch()` against small JSON endpoints** for the genuinely asynchronous
   parts: batch progress polling, OpenAI translate, notification read/unread,
   and per-row actions (approve / request / restore).

Rationale: the current `$queryString` arrays already define the URL contract.
Preserving it keeps deep links and browser history working and keeps the JS
surface small.

## 4. Component -> replacement map

| Current | Replacement | Notes |
|---|---|---|
| `Livewire/AuthComponent.php` | `Controllers/BaseController.php` + `Middleware/EnsureTranslator.php` | `init()` becomes middleware resolving `authUser` + `isAdministrator`; the `create/update/delete` authorization guards become a policy check helper. |
| `Livewire/Login.php` | `Controllers/LoginController.php` | GET renders form, POST authenticates. **Rate limiting must move from `danharrin/livewire-rate-limiting` to Laravel's `Illuminate\Support\Facades\RateLimiter`**, preserving the existing 10-attempt limit and the "Slow down! Please wait another N seconds to log in." message. |
| `Livewire/Languages.php` | `Controllers/LanguageController.php` | index + `create`, `delete`, `importLanguages`, `importTranslations`, `findMissingTranslations`, `approveAllLanguagesTranslations`, `exportTranslationsForAllLanguages`, `deleteJobs`. |
| `Livewire/Translations.php` | `Controllers/TranslationController.php` | index + `showTranslateModal` (JSON), `openAITranslate` (JSON), `updateTranslation`, `updateAllTranslations`, `approveTranslation`, `approveAllTranslations`, `requestTranslation`, `restoreRequestTranslation`, `restoreTranslation`, `exportTranslationsForLanguage`. |
| `Livewire/Translators.php` | `Controllers/TranslatorController.php` | full CRUD + `updateNewPassword`, `notifyPendingTranslations`. |
| `Livewire/Settings.php` | `Controllers/SettingController.php` | **Keep the per-field autosave.** `Settings::updated()` validates and saves one field at a time then calls `Setting::getFreshCached()`. A single form POST is NOT the same end state: it lets an unrelated invalid field block saving a valid one. Implement as `PATCH settings/{field}` via fetch, with per-field validation, inline error, and cache refresh. |
| `Livewire/Manual.php` | `Controllers/ManualController.php` | pure render; move the markdown/anchor logic verbatim into the controller or a small service. |
| `Livewire/BatchExecution.php` | `GET {prefix}/batch/progress` JSON + `resources/js/modules/batch-progress.js` | replaces `wire:poll.visible.1000ms`. |
| `Livewire/LanguagesToastMessage.php` | `resources/js/modules/toast.js`, seeded from session flash | keep the four `MESSAGE_TYPES` as CSS modifiers. |
| `Livewire/FlashMessage.php` | `Controllers/NotificationController.php` + `resources/js/modules/notifications.js` | `markAsRead`, `markAllAsRead`, `updateNotifications`. |
| `Livewire/Traits/DispatchesLegacyEvents.php` | **delete** | already vestigial after Phase 1. |
| `Livewire/Traits/ChecksForRunningJobs.php` | **move** to `src/Services/Traits/ChecksForRunningJobs.php` | **Do not delete.** It is shared with console commands (`$fromCommandLine`, `sendCommandInfo`). Replace its `emitToast()` with a session-flash helper so it works from both HTTP and CLI. |

## 5. Files

### Delete
- `src/Livewire/` (entire directory, except the two traits handled above)
- `resources/views/vendor/livewire/tailwind.blade.php`
- `resources/views/batch-execution.blade.php`, `flash-message.blade.php`,
  `languages-toast-message.blade.php` become partials, not Livewire views.

### Add
- `src/Controllers/{Login,Language,Translation,Translator,Setting,Manual,Notification,BatchController}.php`
- `src/Controllers/BaseController.php`
- `src/Middleware/EnsureTranslator.php`
- `src/Requests/` - form requests carrying the rules currently in `getRules()`
- `resources/js/modules/{toast,modal,batch-progress,notifications,filters,search,confirm}.js`
- `resources/views/partials/pagination.blade.php` - DaisyUI-styled paginator
  replacing the Livewire pagination view

### Modify
- `routes/web.php` - component routes become controller routes
- `src/LanguagesServiceProvider.php` - delete `loadLivewireComponents()` and the
  `Livewire` import; register the new middleware alias
- `resources/views/layouts/app.blade.php` - drop `@livewireStyles`,
  `@livewireScripts`, `<livewire:*/>` tags; include partials directly
- all 24 Blade views - `wire:*` removed, Flowbite classes -> DaisyUI
- `composer.json` - remove `livewire/livewire`, `danharrin/livewire-rate-limiting`
- `package.json` - remove `alpinejs`, `flowbite`; add `daisyui`
- `resources/css/app.css` - `@plugin "daisyui"`, drop the flowbite `@source`
- `tailwind.config.js` - drop `flowbite/plugin`; consider folding the remaining
  config into `app.css` (Tailwind 4 CSS-first) and deleting the file
- `resources/js/bootstrap.js` - drop Alpine and Flowbite imports

## 5a. Remove the GitHub PR export feature

Scope addition (owner request): the GitHub pull-request-on-export feature is to
be removed. It is the entirety of the outside contribution to this branch.

This is a **breaking change** for any installation using it, and the package is
published at v1.9 - it needs a major/minor bump and a changelog note.

Delete outright:
- `src/Services/GitHubPullRequestService.php` (378 lines)
- `src/Jobs/CreateTranslationPullRequestJob.php` (49 lines)

Strip references from:
- `config/languages.php` - the whole `github_pr` block (lines 174-183) and its
  8 `LANGUAGES_GITHUB_PR_*` env keys
- `src/Livewire/Languages.php`, `src/Livewire/Translations.php` - the
  `$createPrOnExport` / `$githubPrEnabled` properties and every `$createPr`
  branch inside the export `finally` closures. **These two files are being
  rewritten as controllers anyway, so simply do not carry the feature across.**
- `src/Console/Commands/ExportTranslations.php`
- `resources/views/languages.blade.php`, `resources/views/translations.blade.php`
  - the PR toggle markup
- `lang/en/translations.php` - `create_pr_label`, `create_pr_tooltip` and
  related strings
- `src/Models/Translator.php`, `src/LanguagesServiceProvider.php`
- `docs/CLI_COMMANDS.md`, `docs/CONFIGURATION.md`, `docs/TROUBLESHOOTING.md`

No test references it, so nothing in `tests/` needs changing for this.

## 5b. Single-project by default, multi-host opt-in

Today multi-host/shared-server coordination is always on and cannot be turned
off: `Settings.php:21` validates `'setting.domains' => 'required'`, so the field
can never be saved empty, and `ChecksForRunningJobs::anotherJobIsRunning()` fires
a synchronous `Http::pool()` at every configured host on **every** gated action
(import, export, approve-all). For the common case - one project, one server -
that is pure latency and a failure surface for no benefit.

Target: the easy path is a single project with no shared hosts. Multi-host
becomes a feature you switch on.

- Add `settings.enable_multi_host` (boolean, default `false`).
- Change validation to `domains` => `nullable`, `required_if:enable_multi_host,true`.
  Remove the unconditional `required`.
- `anotherJobIsRunning()` must skip the HTTP pool entirely when the flag is off
  and check only the local `jobs` / `job_batches` tables.
- `ExportTranslationService::exportTranslationsOnOtherHosts()` becomes a no-op
  when the flag is off.
- The `routes/api.php` endpoints stay registered and keep their contract (other
  installations call them), but they are inert for a single-project install.

**Migration safety - do not get this wrong.** A migration that simply defaults
the column to `false` would silently disable multi-host for every existing
installation that relies on it, and nothing would fail loudly; exports would
just stop propagating. The migration must backfill:

```
enable_multi_host = true  WHERE domains IS NOT NULL AND TRIM(domains) <> ''
```

Add a test for both directions: a fresh install defaults to off; an install with
domains already set comes out of the migration with multi-host on.

Surface the toggle in the Settings page, and document it in
`docs/CONFIGURATION.md` and `docs/APPLICATION_MANUAL.md`.

## 5c. Performance - the translations table has no indexes

Confirmed: no migration adds a single `index()` to `translations`. The only
indexes in the package are `languages.code` (unique), `translators.email`
(unique), `jobs.queue` and `failed_jobs.uuid`.

`translations` is the hot table. It is filtered on `approved`,
`needs_translation`, `updated_translation`, `exported`, `is_vendor` and `type`,
searched with `LIKE '%term%'` across six columns, and looked up constantly by
`shared_identifier` (`Translations.php:224`, `240`).

Add a migration with composite indexes chosen to match the real query shapes -
`language_id` leading, since every translations query is already scoped to a
language:

- `(language_id, approved)`, `(language_id, needs_translation)`,
  `(language_id, updated_translation)`, `(language_id, exported)`,
  `(language_id, type)`
- `shared_identifier` **is a `text` column and cannot be indexed on MySQL/MariaDB
  without a prefix length.** Either change it to `string(191)` (needs a data
  audit first - check the longest existing value) or index a prefix explicitly.
  Do not let this be auto-generated.

Constraints that apply here (owner's standing rules):

- Name every index explicitly and keep the generated name under 60 characters.
  `{table}_{columns}_{type}` overruns MariaDB's 64-char cap easily, and the
  package's table names are configurable and can be long.
- **A green SQLite suite proves nothing here.** Verify on a scratch
  MySQL/MariaDB database: migrate, inspect `SHOW INDEX` and
  `information_schema`, then drop it.
- `LIKE '%term%'` cannot use a B-tree index. Either accept the scan, or add a
  FULLTEXT index and a driver check. Decide deliberately; do not pretend the
  composite indexes fix search.

## 5d. In-app manual must cover all functionality

The mechanism already exists and is kept: `Manual.php` renders
`docs/APPLICATION_MANUAL.md` to HTML with generated heading anchors and a
section index, served at the `languages.manual` route. Keep that - the manual
stays a single markdown file rendered in-app, so there is one source of truth.

The content is the gap. It is 123 lines and documents **none of the 8 artisan
commands**:

`languages:import-languages`, `languages:import-translations`,
`languages:find-missing-translations`, `languages:export-translations`,
`languages:export-after-deployment`, `languages:prune-batches`,
`languages:send-automatic-pending-notifications`, and the developer
download-to-local command.

Expand `docs/APPLICATION_MANUAL.md` to cover every user-facing feature:

- each of the four screens (Languages, Translators, Translations, Settings) with
  every action and filter on it
- every Settings toggle and what it actually changes, including the new
  `enable_multi_host`
- the full CLI reference, with when to use each command and its flags
- the batch/queue model: what runs in the background, why actions are blocked
  while a job runs, how to cancel
- single-project vs multi-host setup, with the simple path first
- the permission model: admin vs translator, and what a translator may touch
- troubleshooting

Drop the GitHub PR section entirely (feature removed, see 5a).

`AGENTS.md` already requires the manual to be updated for every behavior change;
this rewrite is a behavior change, so the manual is part of the definition of
done, not a follow-up.

## 5e. DB loader becomes the default

`db_loader` already exists and works: `LanguagesTranslatorServiceProvider::registerLoader()`
swaps Laravel's `FileLoader` for `TranslationLoader`, which serves
`Translation::getCachedTranslations()` from the database. With it on, nothing is
written to `lang/`, so exported files can never be clobbered by a git checkout,
and with a shared database the multi-host HTTP sync becomes largely redundant.

Changes:

- **Default `db_loader` to `true` for new installs.** The migration must
  backfill existing installs with their *current* value so nobody's mode flips
  silently - same hazard as 5b.
- **Keep file mode supported.** It is a one-line branch that already exists
  (`loadTranslationsArray()`), and some installations legitimately want `lang/`
  files in git for review or static deploys. Do not delete it.
- **Add a cache-version key.** `getCachedTranslations()` uses
  `Cache::rememberForever` with key `cache_key . locale . group . namespace`,
  and `unsetCachedTranslation()` must rebuild that key *exactly* to invalidate.
  Today correctness rests entirely on every write path passing a matching
  group/namespace (import does so at `CanCreateTranslation.php:109`). Because
  there is no TTL, one missed invalidation serves stale translations forever.

  Introduce a monotonically increasing version in the cache key
  (`cache_key . ':v' . $version . locale . group . namespace`) bumped on any
  translation write. A missed targeted invalidation then self-heals on the next
  bump instead of persisting indefinitely.
- **Document the web-request failure mode.** `registerLoader()` falls back to
  the file loader when the DB is unreachable *only* under
  `runningInConsole()`. For web requests `loadTranslationsArray()` goes straight
  to the database. Either add an equivalent guard or state plainly in the manual
  that DB mode makes the database a hard dependency for rendering.

## 6. Behavior that must be preserved exactly

These are the parts most likely to regress silently. Each needs a test.

1. **Authorization - fix, do not preserve.** Non-admin translators may only see
   languages assigned to them (`Translations::mount()` aborts 403;
   `Languages::query()` scopes to `$authUser->languages()`).

   **But the current per-row actions are vulnerable and must NOT be ported
   as-is.** `Translations.php` lines 223, 266, 275, 343 and 379
   (`showTranslateModal`, `restoreRequestTranslation`, `requestTranslation`,
   `approveTranslation`, `restoreTranslation`) all resolve the model with a bare
   `Translation::findOrFail($id)`. `mount()` guards page *access* only, so a
   translator assigned to German can act on a French translation's id today.

   In the rewrite every one of these must resolve through
   `$language->translations()` **and** verify the language is in the
   translator's permitted set for non-admins. Add an explicit regression test:
   a non-admin translator POSTing another language's translation id gets 403
   and the row is unchanged.

   The admin-only guards in `AuthComponent::{create,update,delete}` must survive
   as real server-side checks, not merely hidden buttons.

1a. **Notification ownership - second authorization hole.**
   `FlashMessage::markAsRead(string $id)` updates the `notifications` row by id
   with no `notifiable_id` check, so any authenticated translator can mark
   another translator's notification read. (`markAllAsRead()` is scoped, because
   it uses the ids already loaded for that user; only the single-id path is
   open.) Scope it to the authenticated notifiable and add a regression test.

1b. **Job gating consistency.** `approveTranslation()` is not gated on
   `anotherJobIsRunning()` while `approveAllTranslations()` is. Decide
   deliberately and document which single-row actions are gated.
2. **Login rate limiting.** 10 attempts, same lockout message.
3. **`anotherJobIsRunning()` gating.** Every import/export/approve action is
   gated on it, including the cross-host HTTP pool check. Unreachable hosts
   must continue to be ignored rather than blocking local jobs.
4. **Three-state filters.** `needs_translation`, `approved`,
   `updated_translation`, `exported`, `is_vendor` cycle null -> true -> false ->
   null and round-trip through the query string.
5. **Batch progress.** Polling must stop when the batch finishes and must not
   poll while the tab is hidden (`wire:poll.visible` semantics) - use
   `document.visibilityState`.
6. **The `shared_identifier` translation modal**, including the fallback to the
   fallback-locale example when the chosen example has no value.
7. **Dark mode.** Currently a hand-rolled `localStorage['color-theme']` toggle
   writing `.dark` on `<html>`. DaisyUI uses `data-theme`. The toggle must set
   **both** so the existing `dark:` utility classes keep working, and the stored
   key must stay `color-theme` so existing users don't lose their preference.

## 6a. Browser contract

Livewire supplied behavior that no controller can recreate on its own. These are
implementation requirements, not suggestions - each one is a place the UI can
look right and do nothing.

- **Named form fields.** `wire:model` / `wire:model.defer` bind without a `name`
  attribute. `translations.blade.php:164` (the translate textarea) and the
  translator permission checkboxes have no `name`, so a plain submit posts
  nothing. Every control that participates in a submit needs a real `name`.
- **Draft ownership for the translate modal.** `wire:model.defer="translatedValue"`
  means the textarea holds an unsaved draft that travels with the next action.
  The OpenAI suggestion (`Translations.php:242`) only mutates component state;
  saving is a separate step. Preserve that: fetch the suggestion into the
  textarea, never auto-save, and do not clobber a draft the user has edited.
- **Loading and disabled state.** `wire:loading`, `wire:loading.remove`,
  `wire:loading.attr` and `wire:target` currently drive spinners and button
  disabling. Replace with explicit JS that disables the control and shows the
  spinner for the duration of the fetch, including the error path.
- **Toast payload.** The chain carries message, type (the four
  `LanguagesToastMessage::MESSAGE_TYPES`) and duration, plus a dismissal timer.
  Session flash only covers redirect responses - fetch responses and
  job-completion events need a client-side toast API.
- **Batch startup and recovery.** `BatchExecution::mount()` recovers an
  in-flight batch on page load by querying `job_batches`; `progressBatch` starts
  tracking from an event. Both paths must exist: poll on load if a batch is
  already running, and start polling when an action launches one.
- **Notification polling.** Unread notifications refresh independently of page
  loads. Keep a polling endpoint; honour `document.visibilityState`.
- **Modal control.** `translations.blade.php:233` builds the modal with
  `new window.Modal(...)` - a Flowbite JS class. That is a runtime dependency,
  not styling, so it must be replaced in Stage 1 (see 8).

## 7. Tests

Correction to an earlier draft of this spec: **only 3 of the 7 feature files use
Livewire** - `LanguageTest` (6 calls), `TranslationsTest` (4), `TranslatorTest`
(9). The other four (`ExportTranslationServiceTest`,
`ImportLanguageServiceTest`, `ImportTranslationServiceTest`,
`OpenAITranslationServiceTest`) are service-level tests that need no rewrite.
**Preserve that service coverage as-is.**

Rewrite only those 3 as HTTP tests. Use `#[Test]` attributes (PHPUnit 12 removes
doc-comment metadata).

**Acceptance is a behavior matrix, not a test count.** A count floor is a weak
gate here - `ExportTranslationServiceTest.php:83` literally asserts `true`, so
the current 30 already includes a test that proves nothing. Required cases:

Authorization (each must assert *both* the status and that no row changed):
- non-admin translator POSTs a translation id belonging to a language they are
  not assigned to -> 403, row unchanged (covers the 5 endpoints in 6.1)
- non-admin hits every admin-only endpoint -> 403
- translator marks *another translator's* notification read -> 403, row unchanged
- login lockout after 10 attempts

Behavior:
- settings per-field autosave: one invalid field does not block saving a valid one
- translate modal: AI suggestion populates the draft without saving; save is separate
- batch progress: polls on page load when a batch is already running; stops when finished
- three-state filters round-trip through the query string
- multi-host off -> no outbound HTTP from `anotherJobIsRunning()`
- migration backfill: install with domains set comes out with multi-host ON;
  fresh install comes out OFF (5b), and `db_loader` keeps its existing value (5e)

Pre-existing defects to fix here:
- `LanguageFactory` picks languages at random, so `LanguageTest` fails ~1 run in
  5 when it draws a second "English (...)" variant and the `assertDontSee` after
  searching "English" legitimately matches. Make the fixture deterministic.
- `CanCreateTranslation.php:204` reads `$tempValue` in a `catch` where it is
  assigned as the first statement of the `try`. Initialise it before the `try`.
- `Languages.php:371` - `(bool) Language::first() > 1` casts first and is
  therefore *always false*, so `$hasImportedLanguages` never becomes true and
  the fallback-language banner shows permanently. Fix to `Language::exists()`.

## 8. Staging

Revised: the original two-stage split entangled. Removing Livewire breaks Alpine
`@entangle` bindings and the Flowbite `window.Modal` the translate modal is
built on, so "Stage 2 is only a class swap" was false.

**Stage 1 - behavior and runtime.** Controllers, routes, middleware, form
requests, all vanilla JS modules, and the full browser contract in 6a - including
replacing Flowbite's `window.Modal` and every Alpine binding. Remove
`livewire/livewire` and `danharrin/livewire-rate-limiting` from `composer.json`,
drop `alpinejs` from `package.json`, and delete the Livewire registrations from
the service provider. Keep Flowbite's **CSS classes** in the markup, but the
Flowbite **JS** must already be unused by the end of this stage.

Gate: suite green with the 6a behavior matrix covered; `composer audit` clean;
`npm run production` succeeds; no `wire:`, `livewire`, `@entangle`, `x-data` or
`window.Modal` anywhere in `src/` or `resources/`.

**Stage 2 - styling only.** Flowbite -> DaisyUI class swap across the Blade
views, remove `flowbite` from `package.json`, add `daisyui`, update `app.css`
and `tailwind.config.js`.

Gate: same suite green; `npm run production` succeeds; no `flowbite` or `alpine`
string in `resources/`, `package.json` or the built bundle.

**Stage 3 - data and docs.** The 5b/5c/5e migrations (multi-host flag with
backfill, translations indexes verified on a real MySQL/MariaDB scratch
database, db_loader default with cache versioning), the GitHub PR removal (5a),
and the manual rewrite (5d).

## 9. Constraints for the executor

- PHP `^8.2`, Laravel `^12.0 || ^13.0` (set in Phase 1). Do not use APIs newer
  than Laravel 12.
- Follow the existing code style: typed properties, docblocks on public methods,
  `config('languages.*')` for every table name, guard name and URL segment.
  Never hardcode a table name.
- Tests run on SQLite in-memory via Testbench; keep that.
- Edits only. No commits, no migrations, no `composer`/`npm` install commands.
- Do not touch `routes/api.php` endpoint paths or payloads.
- Update `docs/` and `AGENTS.md` to match (project rule in `AGENTS.md`).
