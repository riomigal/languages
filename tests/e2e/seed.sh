#!/usr/bin/env bash
# Rebuilds only the disposable e2e database. Called before each behavior test.
set -euo pipefail
cd "$(dirname "$0")/../.."
export DB_CONNECTION=sqlite
# config(languages.db_connection) falls back to database.default, which Testbench
# pins to its in-memory "testing" connection; name the connection explicitly.
export LANGUAGES_DB_CONNECTION=sqlite
export DB_DATABASE="$PWD/tests/e2e/.data/e2e.sqlite"
export CACHE_STORE=array
export SESSION_DRIVER=file
export QUEUE_CONNECTION=sync
mkdir -p tests/e2e/.data
: > "$DB_DATABASE"
./vendor/bin/testbench migrate:fresh --force >/dev/null
# Run inside the same Testbench application as the HTTP server. The migration
# supplies the admin and English; these fixtures add a regular translator and
# distinct translation states without depending on imports or external services.
export XDG_CONFIG_HOME="$PWD/tests/e2e/.data/config"
./vendor/bin/testbench tinker --execute="$(cat <<'PHP'
use Illuminate\Support\Facades\Hash;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Setting;
use Riomigal\Languages\Models\Translation;
use Riomigal\Languages\Models\Translator;

$english = Language::where('code', 'en')->firstOrFail();
Language::create(['code' => 'de', 'name' => 'German', 'native_name' => 'Deutsch']);
$translator = Translator::create([
    'email' => 'translator@example.test',
    'password' => Hash::make('translator-password'),
    'first_name' => 'Regular',
    'last_name' => 'Translator',
    'phone' => '+41000000001',
    'admin' => false,
]);
$translator->languages()->sync([$english->id]);
Setting::firstOrFail()->update(['allow_deleting_languages' => true]);

// key, value, needs translation, approved, updated, vendor, exported.
// Every filter has a different matching set, so filtering on the wrong field
// cannot accidentally satisfy the browser assertions.
$rows = [
    ['welcome', 'Welcome home', false, true, false, false, true],
    ['checkout', 'Finish your order', true, false, false, false, false],
    ['profile', 'Your profile', false, false, true, false, false],
    ['vendor_notice', 'Vendor notice', false, true, false, true, false],
    ['vendor_pending', 'Vendor draft', true, false, true, true, false],
    ['vendor_ready', 'Vendor ready', false, true, false, true, true],
];
foreach ($rows as [$key, $value, $needs, $approved, $updated, $vendor, $exported]) {
    Translation::create([
        'language_id' => $english->id,
        'language_code' => $english->code,
        'shared_identifier' => hash('sha256', 'e2e.'.$key),
        'type' => 'php',
        'namespace' => $vendor ? 'e2e-vendor' : '',
        'group' => 'e2e',
        'key' => $key,
        'value' => $value,
        'old_value' => $updated ? 'Previous '.$value : null,
        'needs_translation' => $needs,
        'approved' => $approved,
        'updated_translation' => $updated,
        'is_vendor' => $vendor,
        'exported' => $exported,
    ]);
}
if ($translator->fresh()->admin || $translator->languages()->count() !== 1
    || !Hash::check('translator-password', $translator->fresh()->password)
    || $english->translations()->count() !== count($rows)) {
    throw new RuntimeException('E2E fixtures were not persisted correctly.');
}
echo "seeded: 2 languages, admin + non-admin, 6 translations\n";
PHP
)"
