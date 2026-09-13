#!/usr/bin/env bash
# Rebuilds the e2e database and seeds an admin translator plus a language.
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
echo "seeded"
