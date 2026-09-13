<?php

namespace Riomigal\Languages\Tests\Feature;

use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Translation\FileLoader;
use OpenAI\Laravel\Facades\OpenAI;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Riomigal\Languages\Exceptions\ExportTranslationException;
use Riomigal\Languages\Exceptions\MassCreateTranslationsException;
use Riomigal\Languages\Exceptions\MissingSettingsException;
use Riomigal\Languages\Jobs\ExportUpdatedModelTranslation;
use Riomigal\Languages\Jobs\MassCreateEloquentTranslationsJob;
use Riomigal\Languages\Jobs\UpdateTranslationJob;
use Riomigal\Languages\LanguagesTranslatorServiceProvider;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Setting;
use Riomigal\Languages\Services\ExportTranslationService;
use Riomigal\Languages\Services\ImportTranslationService;
use Riomigal\Languages\Services\OpenAITranslationService;
use Riomigal\Languages\Models\Translation;
use Riomigal\Languages\Tests\BaseTestCase;
use Riomigal\Languages\TranslationLoader;

class MissingTranslationDataTest extends BaseTestCase
{
    use RefreshDatabase;

    #[Test]
    #[DataProvider('settingOperations')]
    public function a_missing_settings_row_raises_an_actionable_exception(string $operation): void
    {
        $this->removeSettings();
        try {
            if ($operation === 'import') {
                (new ImportTranslationService())->importTranslations();
            } else {
                Setting::$operation();
            }
            $this->fail('Expected a missing settings exception.');
        } catch (MissingSettingsException $e) {
            $this->assertStringContainsString('settings row is missing', $e->getMessage());
            $this->assertSame(0, Setting::query()->count());
        }
    }

    public static function settingOperations(): array
    {
        return array_map(fn (string $method): array => [$method], [
            'getCached', 'getFreshCached', 'setJobsRunning', 'multiHostEnabled', 'getDomains', 'import',
        ]);
    }

    #[Test]
    public function restoring_settings_after_a_missing_row_uses_the_saved_values(): void
    {
        $this->removeSettings();
        try {
            Setting::getCached();
            $this->fail('Expected a missing settings exception.');
        } catch (MissingSettingsException) {
            Setting::query()->create(['db_loader' => false, 'enable_open_ai_translations' => true]);
        }

        $this->assertFalse(Setting::getCached()->db_loader);
        $this->assertTrue(Setting::getCached()->enable_open_ai_translations);
    }

    #[Test]
    public function loader_registration_uses_files_when_the_settings_table_has_no_row(): void
    {
        $this->removeSettings();
        $provider = new LanguagesTranslatorServiceProvider($this->app);
        (new ReflectionMethod($provider, 'loadTranslationsArray'))->invoke($provider);

        $this->assertInstanceOf(FileLoader::class, app('translation.loader'));
        $this->assertNotInstanceOf(TranslationLoader::class, app('translation.loader'));
    }

    #[Test]
    public function a_deleted_translation_in_an_export_job_raises_model_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);
        (new ExportUpdatedModelTranslation(999999, 'en'))->handle();
    }

    #[Test]
    public function a_deleted_target_model_is_not_marked_as_exported(): void
    {
        $translation = $this->translation(['type' => 'model', 'namespace' => Language::class, 'group' => 'native_name', 'key' => '999999']);
        try {
            (new ExportUpdatedModelTranslation($translation->id, 'en'))->handle();
            $this->fail('Expected a missing model exception.');
        } catch (ModelNotFoundException $e) {
            $this->assertSame(Language::class, $e->getModel());
            $this->assertSame(['999999'], $e->getIds());
            $this->assertFalse($translation->fresh()->exported);
        }
    }

    #[Test]
    #[DataProvider('invalidModelMetadata')]
    public function invalid_model_metadata_raises_a_domain_exception(array $attributes): void
    {
        $language = Language::query()->firstOrFail();
        $translation = $this->translation(array_replace([
            'type' => 'model', 'namespace' => Language::class, 'group' => 'native_name', 'key' => (string) $language->id,
        ], $attributes));

        $this->expectException(DomainException::class);
        (new ExportUpdatedModelTranslation($translation->id, 'en'))->handle();
    }

    public static function invalidModelMetadata(): array
    {
        return [
            'null namespace' => [['namespace' => null]],
            'empty namespace' => [['namespace' => '']],
            'non-model namespace' => [['namespace' => \stdClass::class]],
            'null column' => [['group' => null]],
            'empty column' => [['group' => '']],
            'missing column' => [['group' => 'missing_column']],
        ];
    }

    #[Test]
    #[DataProvider('nullFileMetadata')]
    public function null_file_metadata_raises_a_package_export_exception(string $field): void
    {
        $translation = $this->translation([$field => null]);

        try {
            (new ExportTranslationService())->exportTranslationForLanguage($translation->language);
            $this->fail('Expected an export exception.');
        } catch (ExportTranslationException $e) {
            $this->assertStringContainsString($field, $e->getMessage());
            $this->assertFalse($translation->fresh()->exported);
        }
    }

    public static function nullFileMetadata(): array
    {
        return [['namespace'], ['group']];
    }

    #[Test]
    #[DataProvider('nullSourceAttributes')]
    public function null_source_attributes_raise_a_package_creation_exception(string $field): void
    {
        $source = $this->translation([$field => null]);
        $target = $this->createFallbackLanguage('de');

        try {
            (new MassCreateEloquentTranslationsJob([$source->id], $target->id, $source->language_id))->handle();
            $this->fail('Expected a creation exception.');
        } catch (MassCreateTranslationsException $e) {
            $this->assertStringContainsString($field, $e->getMessage());
            $this->assertSame(0, $target->translations()->count());
        }
    }

    public static function nullSourceAttributes(): array
    {
        return [['value'], ['namespace'], ['group']];
    }

    #[Test]
    public function updating_without_an_acting_translator_raises_a_domain_exception_before_mutation(): void
    {
        $translation = $this->translation();
        try {
            (new UpdateTranslationJob($translation->language, $translation, 'Changed', null))->handle();
            $this->fail('Expected a missing translator exception.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('acting translator', $e->getMessage());
            $this->assertFalse($translation->isDirty());
            $this->assertSame('Original', $translation->fresh()->value);
        }
    }

    #[Test]
    #[DataProvider('missingLanguages')]
    public function automatic_translation_preserves_input_when_a_language_is_missing(bool $missingSource): void
    {
        $fake = OpenAI::fake();
        $language = Language::query()->firstOrFail();
        $source = $missingSource ? null : $language;
        $target = $missingSource ? $language : null;
        $service = new OpenAITranslationService();

        $this->assertSame('Original', $service->translateString($source, $target, 'Original'));
        $this->assertSame(['key' => 'Original'], $service->translateArray($source, $target, ['key' => 'Original']));
        $fake->assertNothingSent();
    }

    public static function missingLanguages(): array
    {
        return [[true], [false]];
    }

    #[Test]
    public function automatic_translation_preserves_a_null_value_without_sending_it(): void
    {
        $fake = OpenAI::fake();
        $language = Language::query()->firstOrFail();

        $this->assertNull((new OpenAITranslationService())->translateString($language, $language, null));
        $fake->assertNothingSent();
    }

    private function removeSettings(): void
    {
        Setting::query()->delete();
        Cache::forget(config('languages.cache_key') . '_settings');
        Cache::forget(config('languages.cache_key') . '_has_db_loader_on');
    }

    private function translation(array $attributes = []): Translation
    {
        $language = Language::query()->firstOrFail();
        return Translation::query()->create(array_replace([
            'language_id' => $language->id,
            'language_code' => $language->code,
            'shared_identifier' => 'regression',
            'type' => 'php',
            'namespace' => '',
            'group' => 'regression',
            'is_vendor' => false,
            'key' => 'key',
            'value' => 'Original',
            'approved' => true,
            'needs_translation' => false,
            'updated_translation' => false,
            'exported' => false,
        ], $attributes));
    }
}
