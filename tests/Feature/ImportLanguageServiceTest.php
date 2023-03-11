<?php

namespace Riomigal\Languages\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Riomigal\Languages\Jobs\Batch\BatchProcessor;
use Riomigal\Languages\Jobs\ImportLanguagesJob;
use Riomigal\Languages\Jobs\ImportTranslationsJob;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Translation;
use Riomigal\Languages\Models\Translator;
use Riomigal\Languages\Services\ImportLanguageService;
use Riomigal\Languages\Services\ImportTranslationService;
use Riomigal\Languages\Services\MissingTranslationService;
use Riomigal\Languages\Tests\BaseTestCase;

class ImportLanguageServiceTest extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * @var ImportLanguageService
     */
    protected ImportLanguageService $importLanguageService;

    /**
     * @var Translator
     */
    protected Translator $admin;

    public function setUp(): void
    {
        /**
         * Background import languages and translations from ./data folder
         */
        parent::setUp(); // TODO: Change the autogenerated stub
        Language::query()->delete();
        $this->importLanguageService = new ImportLanguageService();
        $languages = Language::all();
        $this->admin = $this->createAdmin($languages);
        $this->actingAs($this->admin);
    }

    /**
     * @test
     */
    public function import_translation_service_languages_successful(): void
    {
        /**
         * Given there are no languages in the database and 4 languages in the lang folder
         */
        Language::query()->delete();
        $this->assertEquals(0, Language::count());

        /**
         * When importing new languages
         */
        ImportLanguagesJob::dispatch($this->importLanguageService, $this->admin);

        /**
         * Then it will create 4 language records
         */
        $this->assertEquals(4, Language::count());
    }
}
