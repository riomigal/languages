<?php

namespace Riomigal\Languages\Services;


use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
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
                ->where('type', '!=', 'model')
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

            Translation::query()
                ->select('id','namespace', 'group', 'key', 'value')
                ->where('language_id', $language->id)
                ->where('type', '=', 'model')
                ->isUpdated(false)
                ->approved()
                ->exported(false)
                ->chunkById(200, function ($translations) use ($language) {
                    foreach ($translations as $translation) {
                        $modelInstance = app($translation->namespace);
                        $tableId = $modelInstance->getKeyName();
                        $modelQuery = DB::table($modelInstance->getTable())->where($tableId, $translation->key);
                        $model = $modelQuery->first();
                        $column = $translation->group;
                        $data = $model->$column;
                        if(is_string($data)) $data = json_decode($data, true);
                        if(is_object($data)) $data = (array) $data;
                        $data[$language->code] = $translation->value;
                        $modelQuery->update([
                            $column => json_encode($data)
                        ]);
                        $translation->update([
                            'exported' => true
                        ]);
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
