<?php

namespace Riomigal\Languages\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Riomigal\Languages\Tests\BaseTestCase;

/**
 * The translations table shipped without a single index while carrying every
 * filter the UI applies and, in db_loader mode, every translation lookup the
 * host application makes.
 */
class TranslationsIndexTest extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * @return list<array{0: string}>
     */
    public static function expectedIndexes(): array
    {
        return [
            ['ltr_lang_code_idx'],
            ['ltr_lang_approved_idx'],
            ['ltr_lang_needs_trans_idx'],
            ['ltr_lang_updated_idx'],
            ['ltr_lang_exported_idx'],
            ['ltr_lang_type_idx'],
            ['ltr_lang_vendor_idx'],
        ];
    }

    #[Test]
    public function the_migration_creates_every_expected_index(): void
    {
        $names = collect(
            Schema::connection(config('languages.db_connection'))
                ->getIndexes(config('languages.table_translations'))
        )->pluck('name')->all();

        foreach (self::expectedIndexes() as [$expected]) {
            $this->assertContains($expected, $names, "Missing index {$expected}");
        }
    }

    #[Test]
    public function every_index_name_stays_well_inside_the_mariadb_limit(): void
    {
        /**
         * MariaDB and MySQL cap identifiers at 64 characters and the failure only
         * appears on the real engine - SQLite enforces no limit, so a green suite
         * proves nothing here. Assert headroom rather than the hard cap.
         */
        foreach (self::expectedIndexes() as [$name]) {
            $this->assertLessThanOrEqual(60, strlen($name), "Index name too long: {$name}");
        }
    }
}
