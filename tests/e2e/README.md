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

The tests use visible text, native roles, and existing field/modal IDs. Some
current form labels target wrapper elements instead of inputs, so those fields
use their stable wrapper IDs. No assertions, selectors, waits, or test actions
depend on framework state, framework attributes, CSS classes, or backend APIs.
Database access is limited to fixture setup. Mutations and their persisted
results are exercised through the browser.

The suite contains 41 tests in seven spec files: 31 new tests in the five behavior
specs, plus the 10 existing smoke tests. The new tests cover language forms and
search, translator creation/editing/assignment/deletion, translation rows/search,
each filter's cycle and URL persistence, saved filter URLs, modal editing,
settings persistence, conditional domains validation, and non-admin permissions.

## Application bugs found during browser verification

These failures were reproduced against the current application. The affected
acceptance tests retain their full assertions as `test.fixme`; the application
was not changed. A successful test command with fixmes does not mean these
behaviors work. Remove the corresponding fixme after resolving each defect.

| Bug | Reproduction and observed result | Affected acceptance tests |
| --- | --- | --- |
| BUG-E2E-01 | As admin on Languages, click Add Language. The select/form never appears. | Adding a language; opening and closing the form (2). |
| BUG-E2E-02 | Type German into the language search, or checkout into translation search. All 2 language rows or all 6 translation rows remain visible. | Both search/clear tests (2). |
| BUG-E2E-03 | On English translations, click State Filters. No visible Needs Translation, Approved, Updated, Is Vendor, or Exported button becomes available. | Five state cycles and five selection/reload tests (10). |
| BUG-E2E-04 | Turn off Allow deleting languages. The switch changes visually, but repeatedly opening Settings in another tab still shows it enabled. | Ordinary setting persistence (1). |
| BUG-E2E-05 | On Translators, click Create Translator or the seeded translator's Edit action. The form never appears. Editing and assignment were checked independently using the seeded account. | Creation, editing, and language assignment (3). |
| BUG-E2E-06 | Open English translations with `?is_vendor=true`. All 6 rows appear instead of the 3 vendor rows. | Vendor bookmark/reload test (1); also blocks vendor selection persistence after BUG-E2E-03 is resolved. |

The working tests include translation modal editing and persistence, translator
deletion, all four permission checks, multi-host validation and persistence, and
bookmarked true/false/unfiltered states for the four other filters. The vendor
bookmark test isolates its URL defect from the broken dropdown.

The multi-host validation currently renders `validation.required_if` rather than
a readable error message in this Testbench application. Its acceptance test
checks the required field and rejection of invalid saved state without depending
on that message text.

To investigate a fixme, change its registration back to `test` (or remove the
vendor test's conditional fixme) and run the affected spec. Playwright retains
screenshots, error context, and traces for failures in the ignored `test-results/`
directory. Do not bypass a broken UI with script injection or direct backend
mutation to make a test pass.
