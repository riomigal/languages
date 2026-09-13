# Browser acceptance tests

Run from the package root with the existing dependencies and Chromium available:

```bash
./tests/e2e/seed.sh
npx playwright test
```

The existing Playwright configuration starts `serve.sh` or reuses the Testbench
server on `127.0.0.1:8099`. `E2E_PORT` changes the port. Keep `workers: 1` and do
not run separate Playwright processes against the same database concurrently.

Every test in the five behavior specs resets the disposable file database through
`seed.sh` before logging in. The existing authentication and screen smoke tests
tolerate this fixture state. Repeated runs do not accumulate created records or
inherit settings, searches, or filter selections from earlier tests.

The seeder runs PHP through Testbench, checks the saved non-admin password and
language assignment, and verifies the translation count. The permissions tests
also verify the seeded credentials through the real login form. Fixtures are:

- The migration's admin: `admin@admin.com` / `aaaaaaaa`.
- A non-admin: `translator@example.test` / `translator-password`, assigned English.
- English and German. French remains available for the add-language test.
- Six English translations with different matching sets for all five state filters.
- Language deletion enabled, so permission tests verify that a non-admin cannot
  see an action that is actually available to the admin.

The tests use visible text, native roles, and existing field/modal IDs. The fields
use their stable wrapper IDs as well as associated native labels. No assertions, selectors, waits, or test actions
depend on framework state, framework attributes, CSS classes, or backend APIs.
Database access is limited to fixture setup. Mutations and their persisted
results are exercised through the browser.

The original suite contains 41 tests in seven spec files: 31 new tests in the five behavior
specs, plus the 10 existing smoke tests. The new tests cover language forms and
search, translator creation/editing/assignment/deletion, translation rows/search,
each filter's cycle and URL persistence, saved filter URLs, modal editing,
settings persistence, conditional domains validation, and non-admin permissions.

## Controller rewrite

All 19 previously disabled cases are normal tests now: two language-form cases,
two searches, ten state-filter interactions, settings persistence, three translator
form cases, and the bookmarked vendor filter. The original 41 scenarios and their
behavioral assertions remain. The async modal specs additionally cover AI drafts
and a failed suggestion followed by retry.

Navigation, search and filtering now load full pages. Settings save through a
separate POST form per field, submitted on change. The wrapper IDs used by these
tests are retained and labels now target real named inputs. The modal uses a native
dialog and a named textarea, with data and suggestions loaded through JSON endpoints.

Browser tests require permission to bind a local server port and start Chromium.
In a restricted sandbox, a server "Operation not permitted" or Chromium Mach port
error blocks the suite before the scenarios run. Do not count a test listing or
successful fixture setup as a browser pass. No scenarios may be skipped to hide a
failure. Failure screenshots and traces are saved under the ignored `test-results/`.

The async suggestion tests mock only the external suggestion response; loading,
editing, saving and persisted values still go through the real UI. Server-side tests
separately verify suggestion authorization, example selection and absence of writes.
