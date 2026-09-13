<?php

namespace Riomigal\Languages\Tests\Feature;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Riomigal\Languages\LanguagesTranslatorServiceProvider;
use Riomigal\Languages\Models\Setting;
use Riomigal\Languages\Tests\BaseTestCase;
use Riomigal\Languages\TranslationLoader;

class DbLoaderMigrationTest extends BaseTestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_fresh_install_enables_the_db_loader_in_the_initial_row_and_column_default(): void
    {
        $this->assertTrue(Setting::query()->firstOrFail()->db_loader);
        $this->assertTrue(Setting::getCached()->db_loader);
        $this->assertTrue(Setting::query()->create([])->fresh()->db_loader);
    }

    #[Test]
    public function fresh_installs_discard_file_mode_cached_before_the_settings_table_existed(): void
    {
        Cache::forever(config('languages.cache_key') . '_has_db_loader_on', false);

        $this->migration()->up();
        (new LanguagesTranslatorServiceProvider($this->app))->register();

        $this->assertInstanceOf(TranslationLoader::class, app('translation.loader'));
    }

    #[Test]
    #[DataProvider('savedModes')]
    public function upgrades_and_rollbacks_preserve_every_existing_settings_attribute(bool $enabled): void
    {
        $migration = $this->migration();
        $migration->down();
        $setting = Setting::query()->firstOrFail();
        $setting->update(['db_loader' => $enabled, 'domains' => 'https://one.example']);
        $before = $setting->fresh()->getAttributes();
        Setting::getFreshCached();

        // Even rerunning the original migration must not reseed an existing install.
        $create = require __DIR__ . '/../../database/migrations/2023_04_30_184823_create_settings_table.php';
        $create->up();
        $migration->up();
        $migration->up();

        $this->assertSame($before, $setting->fresh()->getAttributes());
        $this->assertSame($enabled, Setting::getCached()->db_loader);
        $newSetting = Setting::query()->create([])->fresh();
        $this->assertTrue($newSetting->db_loader);

        $migration->down();
        $migration->down();

        $this->assertSame($before, $setting->fresh()->getAttributes());
        $this->assertTrue($newSetting->fresh()->db_loader);
        $this->assertFalse(Setting::query()->create([])->fresh()->db_loader);
    }

    public static function savedModes(): array
    {
        return ['file mode' => [false], 'DB mode' => [true]];
    }

    #[Test]
    public function the_default_migration_uses_configured_names_and_guards_missing_tables_and_columns(): void
    {
        config()->set('database.connections.db_loader_migration', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        config()->set('languages.db_connection', 'db_loader_migration');
        config()->set('languages.table_settings', 'custom_settings');
        $schema = Schema::connection('db_loader_migration');
        $migration = $this->migration();

        $migration->up();
        $migration->down();
        $this->assertFalse($schema->hasTable('custom_settings'));

        $schema->create('custom_settings', function (Blueprint $table) {
            $table->id();
        });
        $migration->up();
        $migration->down();
        $this->assertFalse($schema->hasColumn('custom_settings', 'db_loader'));

        $schema->table('custom_settings', function (Blueprint $table) {
            $table->boolean('db_loader')->default(false);
        });
        $table = DB::connection('db_loader_migration')->table('custom_settings');
        $table->insert([['db_loader' => false], ['db_loader' => true]]);
        $before = $table->orderBy('id')->get()->all();

        $migration->up();
        $this->assertEquals($before, $table->get()->all());
        $id = $table->insertGetId([]);
        $this->assertTrue((bool) $table->where('id', $id)->value('db_loader'));

        $migration->down();
        $id = $table->insertGetId([]);
        $this->assertFalse((bool) DB::connection('db_loader_migration')->table('custom_settings')->where('id', $id)->value('db_loader'));
        $this->assertTrue((bool) DB::connection('testbench')->table('languages_settings')->value('db_loader'));
    }

    private function migration(): Migration
    {
        return require __DIR__ . '/../../database/migrations/2026_09_13_000002_default_db_loader_to_true.php';
    }
}
