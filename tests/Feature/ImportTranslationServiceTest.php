<?php

namespace Riomigal\Languages\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Riomigal\Languages\Helpers\LanguageHelper;
use Riomigal\Languages\Jobs\FindMissingTranslationsJob;
use Riomigal\Languages\Jobs\ImportLanguagesJob;
use Riomigal\Languages\Jobs\ImportTranslationsJob;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Setting;
use Riomigal\Languages\Models\Translation;
use Riomigal\Languages\Models\Translator;
use Riomigal\Languages\Services\ImportLanguageService;
use Riomigal\Languages\Services\ImportTranslationService;
use Riomigal\Languages\Services\MissingTranslationService;
use Riomigal\Languages\Tests\BaseTestCase;

class ImportTranslationServiceTest extends BaseTestCase
{
    use RefreshDatabase;

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
        $languages = Language::all();
        $this->admin = $this->createAdmin($languages);
        $this->actingAs($this->admin);
        ImportLanguagesJob::dispatch((new ImportLanguageService()), $this->admin);
    }

    /**
     * @test
     */
    public function import_translation_service_translations_successful(): void
    {
        /**
         * Given there are 4 languages imported and no translations
         */
        $this->assertEquals(0, Translation::count());

        /**
         * When importing the translations
         */
        ImportTranslationsJob::dispatch(new ImportTranslationService(), $this->admin);

        /**
         * Then it will create Database entry for each translation value excluding vendor translations
         */
        $total = resolve(LanguageHelper::class)->count_all_array_values_in_directory($this->getTempDataPath());
        $this->assertEquals($total, Translation::count());
    }

    /**
     * @test
     */
    public function import_translation_service_missing_translations_successful(): void
    {
        /**
         * Given there are 4 languages imported and translations AND one new language without translations
         */
        ImportTranslationsJob::dispatch(new ImportTranslationService(), $this->admin);

        $total = count(array_unique(Translation::selectRaw('count(*) as total')->groupBy('language_id')->orderBy('language_id')->pluck('total')->all()));

        $this->assertNotEquals(1, $total);

        /**
         * When finding new missing translations
         */
        FindMissingTranslationsJob::dispatch(new MissingTranslationService(), $this->admin);

        /**
         * Then it will create the missing translations entries for each language
         */
        $total = count(array_unique(Translation::selectRaw('count(*) as total')->groupBy('language_id')->orderBy('language_id')->pluck('total')->all()));

        $this->assertEquals(1, $total);


    }

}
