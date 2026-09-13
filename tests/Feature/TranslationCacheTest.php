<?php

namespace Riomigal\Languages\Tests\Feature;

use Illuminate\Cache\FileStore;
use Illuminate\Cache\Repository;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Riomigal\Languages\LanguagesTranslatorServiceProvider;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Setting;
use Riomigal\Languages\Models\Translation;
use Riomigal\Languages\Models\Translator;
use Riomigal\Languages\Services\ApproveLanguagesService;
use Riomigal\Languages\Services\ImportTranslationService;
use Riomigal\Languages\Tests\BaseTestCase;
use Riomigal\Languages\TranslationLoader;

class TranslationCacheTest extends BaseTestCase
{
    // Real commits are necessary to exercise shared caching and after-commit invalidation.
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->refreshTestDatabase();
        // Each test discards its in-memory DB. Legacy rollbacks add required columns
        // without defaults and cannot run against the populated translation fixtures.
        $this->beforeApplicationDestroyed(static function (): void {
            RefreshDatabaseState::$migrated = false;
        });
    }

    #[Test]
    public function bumping_the_version_recovers_a_missed_targeted_invalidation(): void
    {
        $translation = $this->translation();
        $this->assertSame(['welcome' => 'Before'], Translation::getCachedTranslations('en', 'messages', ''));
        $version = $this->version();

        $this->writeWithoutInvalidation($translation, 'After');
        $this->assertSame(['welcome' => 'Before'], Translation::getCachedTranslations('en', 'messages', ''));

        Translation::bumpCacheVersion();

        $this->assertGreaterThan($version, $this->version());
        $this->assertSame(['welcome' => 'After'], Translation::getCachedTranslations('en', 'messages', ''));
    }

    #[Test]
    public function an_unrelated_write_recovers_a_stale_group(): void
    {
        $translation = $this->translation();
        Translation::getCachedTranslations('en', 'messages', '');
        $this->writeWithoutInvalidation($translation, 'After');

        $this->translation(['key' => 'other', 'group' => 'other']);

        $this->assertSame(['welcome' => 'After'], Translation::getCachedTranslations('en', 'messages', ''));
    }

    #[Test]
    #[DataProvider('cacheScopes')]
    public function targeted_invalidation_removes_the_matching_entry_without_bumping_the_version(
        ?string $storedGroup,
        ?string $storedNamespace,
        ?string $group,
        ?string $namespace,
    ): void {
        $translation = $this->translation(['group' => $storedGroup, 'namespace' => $storedNamespace]);
        $this->assertSame(['welcome' => 'Before'], Translation::getCachedTranslations('en', $group, $namespace));
        $version = $this->version();
        $this->writeWithoutInvalidation($translation, 'After');
        $this->assertSame(['welcome' => 'Before'], Translation::getCachedTranslations('en', $group, $namespace));

        Translation::unsetCachedTranslation('en', $group, $namespace);

        $this->assertSame($version, $this->version());
        $this->assertSame(['welcome' => 'After'], Translation::getCachedTranslations('en', $group, $namespace));
    }

    public static function cacheScopes(): array
    {
        return [
            'null group and namespace' => [null, null, null, null],
            'empty group and namespace' => ['', '', '', ''],
            'PHP group' => ['messages', '', 'messages', ''],
            'vendor namespace' => ['messages', 'vendor', 'messages', 'vendor'],
            'JSON wildcard lookup' => ['', '', '*', '*'],
            'PHP wildcard namespace' => ['messages', '', 'messages', '*'],
            'wildcard group' => ['messages', 'vendor', '*', 'vendor'],
        ];
    }

    #[Test]
    public function targeted_invalidation_leaves_other_scopes_cached(): void
    {
        $first = $this->translation();
        $second = $this->translation(['group' => 'other']);
        Translation::getCachedTranslations('en', 'messages', '');
        Translation::getCachedTranslations('en', 'other', '');
        $this->writeWithoutInvalidation($first, 'After');
        $this->writeWithoutInvalidation($second, 'After');

        Translation::unsetCachedTranslation('en', 'messages', '');

        $this->assertSame(['welcome' => 'After'], Translation::getCachedTranslations('en', 'messages', ''));
        $this->assertSame(['welcome' => 'Before'], Translation::getCachedTranslations('en', 'other', ''));
    }

    #[Test]
    public function creates_updates_approvals_and_deletes_refresh_cached_content(): void
    {
        $this->assertSame([], Translation::getCachedTranslations('en', 'messages', ''));
        $version = $this->version();
        $translation = $this->translation();
        $this->assertGreaterThan($version, $this->version());
        $this->assertSame(['welcome' => 'Before'], Translation::getCachedTranslations('en', 'messages', ''));

        $version = $this->version();
        $translation->update(['value' => 'Draft', 'old_value' => 'Approved before', 'approved' => false]);
        $this->assertGreaterThan($version, $this->version());
        $this->assertSame(['welcome' => 'Approved before'], Translation::getCachedTranslations('en', 'messages', ''));

        $translation->update(['approved' => true, 'old_value' => null]);
        $this->assertSame(['welcome' => 'Draft'], Translation::getCachedTranslations('en', 'messages', ''));

        $version = $this->version();
        $translation->delete();
        $this->assertGreaterThan($version, $this->version());
        $this->assertSame([], Translation::getCachedTranslations('en', 'messages', ''));
    }

    #[Test]
    public function changing_a_translations_scope_invalidates_both_old_and_new_lookups(): void
    {
        $translation = $this->translation();
        Translation::getCachedTranslations('en', 'messages', '');
        Translation::getCachedTranslations('en', 'renamed', 'vendor');

        $translation->update(['group' => 'renamed', 'namespace' => 'vendor', 'key' => 'new_key']);

        $this->assertSame([], Translation::getCachedTranslations('en', 'messages', ''));
        $this->assertSame(['new_key' => 'Before'], Translation::getCachedTranslations('en', 'renamed', 'vendor'));
    }

    #[Test]
    public function bulk_imports_refresh_wildcard_lookups_missed_by_targeted_invalidation(): void
    {
        $this->assertSame([], Translation::getCachedTranslations('en', 'auth', '*'));
        $version = $this->version();

        (new ImportTranslationService())->importTranslations();

        $this->assertGreaterThan($version, $this->version());
        $expected = require $this->getDataPath() . '/en/auth.php';
        $this->assertEquals($expected, Translation::getCachedTranslations('en', 'auth', '*'));
    }

    #[Test]
    public function bulk_approvals_refresh_wildcard_lookups(): void
    {
        $translation = $this->translation(['approved' => false, 'old_value' => 'Published']);
        $this->assertSame(['welcome' => 'Published'], Translation::getCachedTranslations('en', 'messages', '*'));
        $version = $this->version();

        (new ApproveLanguagesService())->approveLanguages($translation->language, Translator::query()->firstOrFail()->id);

        $this->assertGreaterThan($version, $this->version());
        $this->assertSame(['welcome' => 'Before'], Translation::getCachedTranslations('en', 'messages', '*'));
    }

    #[Test]
    public function deleting_a_language_invalidates_its_cascaded_translations(): void
    {
        Schema::connection(config('languages.db_connection'))->enableForeignKeyConstraints();
        $translation = $this->translation();
        Translation::getCachedTranslations('en', 'messages', '');

        $translation->language->delete();

        $this->assertSame(0, Translation::query()->count());
        $this->assertSame([], Translation::getCachedTranslations('en', 'messages', ''));
    }

    #[Test]
    #[DataProvider('transactionOutcomes')]
    public function transactions_do_not_publish_uncommitted_values(bool $commit): void
    {
        $translation = $this->translation();
        $this->assertSame(['welcome' => 'Before'], Translation::getCachedTranslations('en', 'messages', ''));
        $version = $this->version();
        $connection = $translation->getConnection();
        $connection->beginTransaction();
        $translation->update(['value' => 'After']);

        $this->assertSame($version, $this->version());
        $this->assertSame(['welcome' => 'After'], Translation::getCachedTranslations('en', 'messages', ''));

        if ($commit) {
            $connection->commit();
            $this->assertGreaterThan($version, $this->version());
        } else {
            $connection->rollBack();
            $this->assertSame($version, $this->version());
        }

        $this->assertSame(['welcome' => $commit ? 'After' : 'Before'], Translation::getCachedTranslations('en', 'messages', ''));
    }

    public static function transactionOutcomes(): array
    {
        return ['commit' => [true], 'rollback' => [false]];
    }

    #[Test]
    public function numeric_string_versions_from_redis_are_stable_and_incremented(): void
    {
        $translation = $this->translation();
        Translation::getCachedTranslations('en', 'messages', '');
        $version = $this->version();
        Cache::forever(config('languages.cache_key') . ':version', (string) $version);
        $this->writeWithoutInvalidation($translation, 'After');

        $this->assertSame(['welcome' => 'Before'], Translation::getCachedTranslations('en', 'messages', ''));
        Translation::bumpCacheVersion();
        $this->assertGreaterThan($version, $this->version());
        $this->assertSame(['welcome' => 'After'], Translation::getCachedTranslations('en', 'messages', ''));
    }

    #[Test]
    public function losing_only_the_version_does_not_reuse_old_cached_content(): void
    {
        $translation = $this->translation();
        Translation::getCachedTranslations('en', 'messages', '');
        $version = $this->version();
        $this->writeWithoutInvalidation($translation, 'After');
        Cache::forget(config('languages.cache_key') . ':version');

        $this->assertSame(['welcome' => 'After'], Translation::getCachedTranslations('en', 'messages', ''));
        $this->assertGreaterThan($version, $this->version());
    }

    #[Test]
    public function the_file_cache_shares_versions_between_independent_repositories(): void
    {
        $directory = sys_get_temp_dir() . '/languages-cache-' . bin2hex(random_bytes(8));
        $original = Cache::getFacadeRoot();
        try {
            Cache::swap(new Repository(new FileStore(app('files'), $directory)));
            $translation = $this->translation();
            Translation::getCachedTranslations('en', 'messages', '');
            $version = $this->version();

            Cache::swap(new Repository(new FileStore(app('files'), $directory)));
            $this->assertSame($version, $this->version());
            $translation->update(['value' => 'After']);

            Cache::swap(new Repository(new FileStore(app('files'), $directory)));
            $this->assertGreaterThan($version, $this->version());
            $this->assertSame(['welcome' => 'After'], Translation::getCachedTranslations('en', 'messages', ''));
        } finally {
            Cache::swap($original);
            File::deleteDirectory($directory);
        }
    }

    #[Test]
    public function fresh_settings_register_the_db_loader_and_file_mode_stays_available(): void
    {
        $translation = $this->translation();
        (new LanguagesTranslatorServiceProvider($this->app))->register();
        $loader = app('translation.loader');
        $this->assertInstanceOf(TranslationLoader::class, $loader);
        $this->assertSame(['welcome' => 'Before'], $loader->load('en', 'messages', '*'));

        $translation->update(['value' => 'After']);
        $this->assertSame(['welcome' => 'After'], $loader->load('en', 'messages', '*'));

        Setting::query()->firstOrFail()->update(['db_loader' => false]);
        Setting::getFreshCached();
        (new LanguagesTranslatorServiceProvider($this->app))->register();
        $this->assertNotInstanceOf(TranslationLoader::class, app('translation.loader'));
        $this->assertEquals(require $this->getDataPath() . '/en/auth.php', app('translation.loader')->load('en', 'auth', '*'));
    }

    private function translation(array $attributes = []): Translation
    {
        return Translation::query()->create(array_merge([
            'language_id' => Language::query()->where('code', 'en')->firstOrFail()->id,
            'language_code' => 'en',
            'shared_identifier' => 'cache-test',
            'type' => 'php',
            'namespace' => '',
            'group' => 'messages',
            'key' => 'welcome',
            'value' => 'Before',
            'approved' => true,
            'needs_translation' => false,
        ], $attributes));
    }

    private function version(): int
    {
        return (int) Cache::get(config('languages.cache_key') . ':version');
    }

    private function writeWithoutInvalidation(Translation $translation, string $value): void
    {
        DB::connection(config('languages.db_connection'))->table(config('languages.table_translations'))
            ->where('id', $translation->id)->update(['value' => $value]);
    }
}
