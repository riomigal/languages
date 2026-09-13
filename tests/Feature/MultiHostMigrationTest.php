<?php

namespace Riomigal\Languages\Tests\Feature;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Riomigal\Languages\Models\Setting;
use Riomigal\Languages\Tests\BaseTestCase;

class MultiHostMigrationTest extends BaseTestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_fresh_install_defaults_to_multi_host_off(): void
    {
        $this->assertFalse(Setting::query()->firstOrFail()->enable_multi_host);
        $this->assertFalse(Setting::multiHostEnabled());

        $newSetting = Setting::query()->create([])->fresh();
        $this->assertFalse($newSetting->enable_multi_host);
    }

    #[Test]
    #[DataProvider('existingDomains')]
    public function the_migration_preserves_existing_host_configuration(?string $domains, bool $enabled): void
    {
        $migration = $this->migration();
        $migration->down();

        DB::connection(config('languages.db_connection'))
            ->table(config('languages.table_settings'))
            ->update(['domains' => $domains]);

        // An installation may already have cached its settings before migrating.
        $this->assertArrayNotHasKey('enable_multi_host', Setting::getFreshCached()->getAttributes());

        $migration->up();

        $this->assertSame($enabled, Setting::query()->firstOrFail()->enable_multi_host);
        $this->assertSame($enabled, Setting::multiHostEnabled());
        $this->assertSame($domains, Setting::getCached()->domains);
    }

    public static function existingDomains(): array
    {
        return [
            'null' => [null, false],
            'empty' => ['', false],
            'spaces' => ['   ', false],
            'one host' => ['https://one.example', true],
            'multiple hosts' => ['https://one.example,https://two.example', true],
            'padded host' => ['  https://one.example  ', true],
        ];
    }

    #[Test]
    public function rerunning_the_migration_does_not_reenable_an_explicitly_disabled_setting(): void
    {
        Setting::query()->firstOrFail()->update([
            'domains' => 'https://one.example',
            'enable_multi_host' => false,
        ]);

        $this->migration()->up();

        $this->assertFalse(Setting::query()->firstOrFail()->enable_multi_host);
    }

    #[Test]
    public function rollback_removes_only_the_flag_and_can_be_repeated(): void
    {
        Setting::query()->firstOrFail()->update(['domains' => 'https://one.example']);
        $schema = Schema::connection(config('languages.db_connection'));
        $tableName = config('languages.table_settings');
        $migration = $this->migration();

        $migration->down();
        $migration->down();

        $this->assertFalse($schema->hasColumn($tableName, 'enable_multi_host'));
        $this->assertSame('https://one.example', Setting::query()->firstOrFail()->domains);

        $migration->up();

        $this->assertTrue(Setting::multiHostEnabled());
    }

    #[Test]
    public function the_migration_uses_the_configured_connection_and_table_and_guards_missing_tables(): void
    {
        config()->set('database.connections.multi_host_migration', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        config()->set('languages.db_connection', 'multi_host_migration');
        config()->set('languages.table_settings', 'custom_settings');

        $schema = Schema::connection('multi_host_migration');
        $migration = $this->migration();

        $migration->up();
        $migration->down();
        $this->assertFalse($schema->hasTable('custom_settings'));

        $schema->create('custom_settings', function (Blueprint $table) {
            $table->id();
            $table->string('domains')->nullable();
        });
        DB::connection('multi_host_migration')->table('custom_settings')->insert([
            ['domains' => 'https://one.example'],
            ['domains' => null],
        ]);

        $migration->up();

        $this->assertSame([true, false], Setting::query()->orderBy('id')->get()
            ->map(fn (Setting $setting): bool => $setting->enable_multi_host)->all());
        $this->assertSame(0, DB::connection('testbench')
            ->table('languages_settings')->where('enable_multi_host', true)->count());

        $migration->down();
        $this->assertFalse($schema->hasColumn('custom_settings', 'enable_multi_host'));
    }

    private function migration(): Migration
    {
        return require __DIR__ . '/../../database/migrations/2026_09_13_000001_add_enable_multi_host_to_settings_table.php';
    }
}
