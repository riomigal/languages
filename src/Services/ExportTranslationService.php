<?php

namespace Riomigal\Languages\Services;


use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Riomigal\Languages\Exceptions\ExportTranslationException;
use Riomigal\Languages\Jobs\ExportUpdatedTranslation;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Translation;
use Riomigal\Languages\Services\Traits\CanExportTranslation;

class ExportTranslationService
{
    use CanExportTranslation;

    /**
     * @var null|Batch
     */
    protected null|Batch $batch = null;

    /**
     * @var string
     */
    protected string $tempLangFolder = 'temp-language-dir';


    /**
     * Exports all languages
     *
     * @param $languages
     * @param null|Batch $batch
     * @return void
     * @throws ExportTranslationException
     */
    public function exportAllTranslations($languages, null|Batch $batch = null): void
    {
        $this->batch = $batch;
        $languages->each(function ($language) {
            $this->exportTranslationForLanguage($language);
        });

    }

    /**
     * Exports a single language
     *
     * @param Language $language
     * @param null|Batch $batch
     * @return void
     * @throws ExportTranslationException
     */
    public function exportTranslationForLanguage(Language $language, null|Batch $batch = null): void
    {
        if ($batch) {
            $this->batch = $batch;
        }
        $tempDirectory = App::langPath($this->tempLangFolder);
        $tempLangDirectory = $tempDirectory . '/' . $language->code;
        $languageDirectory = App::langPath($language->code);
        File::copyDirectory($languageDirectory, $tempLangDirectory);
        try {
            Translation::query()
                ->select('namespace', 'group', 'is_vendor', 'type')
                ->where('language_id', $language->id)
                ->isUpdated(false)
                ->approved()
                ->exported(false)
                ->groupBy('namespace', 'group', 'is_vendor', 'type')
                ->orderBy('group')
                ->chunk(200, function ($translations) use ($language) {
                    foreach ($translations as $translation) {
                        if ($this->batch) {
                            $this->batch->add([new ExportUpdatedTranslation($translation->type, $language->code, $translation->is_vendor, $translation->namespace, $translation->group)]);
                        } else {
                            $this->updateTranslation($translation->type, $language->code, $translation->is_vendor, $translation->namespace, $translation->group);
                        }
                    }
                });
        } catch (\Exception $e) {
            File::deleteDirectory($languageDirectory);
            File::copyDirectory($tempLangDirectory, $languageDirectory);
            File::deleteDirectory($tempDirectory);
            throw new ExportTranslationException($e->getMessage(), __('languages::exceptions.export_language_error', ['language' => $language->native_name]), 0);
        }
        File::deleteDirectory($tempDirectory);
    }
}
