<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The translations table shipped with no indexes at all beyond the primary key
 * and the language_id foreign key. It is the hot table: the UI filters it on
 * five boolean flags and the type enum, and in db_loader mode every translation
 * lookup in the host application reads it by language_code.
 *
 * Index names are given explicitly and kept short. Laravel derives names as
 * {table}_{columns}_{type}, and the table name is configurable, so an
 * auto-generated composite name overruns MySQL/MariaDB's 64 character limit
 * easily - and that failure only appears on the real engine, never on SQLite.
 *
 * Not indexed here: shared_identifier, group and namespace are text columns,
 * which MySQL cannot index without a prefix length and SQLite cannot index by
 * prefix at all. Covering those needs either a column type change or a
 * generated hash column, which is a separate decision with a data audit
 * attached.
 */
return new class extends Migration
{
    /**
     * @var array<string, list<string>>
     */
    private array $indexes = [
        'ltr_lang_code_idx'        => ['language_code'],
        'ltr_lang_approved_idx'    => ['language_id', 'approved'],
        'ltr_lang_needs_trans_idx' => ['language_id', 'needs_translation'],
        'ltr_lang_updated_idx'     => ['language_id', 'updated_translation'],
        'ltr_lang_exported_idx'    => ['language_id', 'exported'],
        'ltr_lang_type_idx'        => ['language_id', 'type'],
        'ltr_lang_vendor_idx'      => ['language_id', 'is_vendor'],
    ];

    public function up(): void
    {
        $connection = $this->connectionName();
        $table = $this->tableName();

        if (!Schema::connection($connection)->hasTable($table)) {
            return;
        }

        $existing = $this->existingIndexNames($connection, $table);

        Schema::connection($connection)->table($table, function (Blueprint $blueprint) use ($existing): void {
            foreach ($this->indexes as $name => $columns) {
                if (in_array($name, $existing, true)) {
                    continue;
                }

                $blueprint->index($columns, $name);
            }
        });
    }

    public function down(): void
    {
        $connection = $this->connectionName();
        $table = $this->tableName();

        if (!Schema::connection($connection)->hasTable($table)) {
            return;
        }

        $existing = $this->existingIndexNames($connection, $table);

        Schema::connection($connection)->table($table, function (Blueprint $blueprint) use ($existing): void {
            foreach (array_keys($this->indexes) as $name) {
                if (!in_array($name, $existing, true)) {
                    continue;
                }

                $blueprint->dropIndex($name);
            }
        });
    }

    private function connectionName(): ?string
    {
        $connection = config('languages.db_connection');

        return is_string($connection) ? $connection : null;
    }

    private function tableName(): string
    {
        $table = config('languages.table_translations');

        if (!is_string($table) || $table === '') {
            throw new RuntimeException('languages.table_translations must be a non-empty string.');
        }

        return $table;
    }

    /**
     * The index names already present on the table.
     *
     * Never fall back to "no indexes found" for an unrecognised driver: that
     * path would let this migration report success while creating nothing, or
     * fail confusingly on a second run.
     *
     * @return list<string>
     */
    private function existingIndexNames(?string $connection, string $table): array
    {
        $names = [];

        foreach (Schema::connection($connection)->getIndexes($table) as $index) {
            $names[] = $index['name'];
        }

        return $names;
    }
};
