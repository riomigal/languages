<?php

namespace Riomigal\Languages\Services;

use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Riomigal\Languages\Exceptions\ExportTranslationException;
use Riomigal\Languages\Jobs\ExportUpdatedModelTranslation;
use Riomigal\Languages\Jobs\ExportUpdatedTranslation;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Translation;
use Riomigal\Languages\Models\Translator;
use Riomigal\Languages\Notifications\FlashMessage;
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
     * @var bool
     */
    protected bool $forceExportAll = false;

    public function exportTranslationsOnOtherHosts(): void
    {
        $hosts = explode(',', config('languages.multiple_db_hosts'));
        if(count($hosts) < 1) return;

        $hosts = array_diff($hosts, [request()->getSchemeAndHttpHost()]);

        $path = route('languages.api.force-export', [], false);

        foreach($hosts as $host) {
            $response = Http::post($host . $path, ['api_key' => config('languages.api_shared_api_key')]);
            if($response->ok()) {
                $message = $response->json()['message'];
            } else {
                $message = __('languages::translations.export_on_other_host_start_failed', ['host' => $host]);
                $response->throw();
            }
            Translator::query()->admin()->each(function (Translator $translator) use ($message) {
                $translator->notify(new FlashMessage( $message));
            });
        }
    }

    /**
     * @param Language $language
     * @param Batch|null $batch
     * @param bool $exportOnlyModels
     * @return void
     * @throws ExportTranslationException
     */
    public function forceExportTranslationForLanguage(Language $language, null|Batch $batch = null, bool $exportOnlyModels = false): void
    {
        $this->forceExportAll = true;
        $this->exportTranslationForLanguage($language, $batch, $exportOnlyModels);
    }


    /**
     * @param Language $language
     * @param Batch|null $batch
     * @param bool $exportOnlyModels
     * @return void
     * @throws ExportTranslationException
     */
    public function exportTranslationForLanguage(Language $language, null|Batch $batch = null, bool $exportOnlyModels = false): void
    {
        try {
            if (!$exportOnlyModels) {
                $this->exportFileTranslationForLanguage($language, $batch);
            }
            $this->exportModelTranslationForLanguage($language, $batch);
        } catch(\Exception $e) {
            throw new ExportTranslationException($e->getMessage(), __('languages::exceptions.export_language_error', ['language' => $language->native_name]), 0);
        }

    }

    /**
     * @param Language $language
     * @param Batch|null $batch
     * @return void
     * @throws ExportTranslationException
     */
    protected function exportFileTranslationForLanguage(Language $language, null|Batch $batch = null): void
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
                ->when(!$this->forceExportAll, function($query) {
                    $query->exported(false);
                })
                ->groupBy('namespace', 'group', 'is_vendor', 'type')
                ->orderBy('group')
                ->chunk(200, function ($translations) use ($language) {
                    foreach ($translations as $translation) {
                        if ($this->batch) {
                            $this->batch->add([new ExportUpdatedTranslation($translation->type, $language->code, $translation->is_vendor, $translation->namespace, $translation->group, $this->forceExportAll)]);
                        } else {
                            $this->updateTranslation($translation->type, $language->code, $translation->is_vendor, $translation->namespace, $translation->group, $this->forceExportAll);
                        }
                    }
                });
        } catch (\Exception $e) {
            Log::error('ExportTranslationService::exportFileTranslationForLanguage.', [
                'ErrorMessage' => $e->getMessage()
            ]);
            File::deleteDirectory($languageDirectory);
            File::copyDirectory($tempLangDirectory, $languageDirectory);
            File::deleteDirectory($tempDirectory);
            throw $e;
        }
        File::deleteDirectory($tempDirectory);
    }


    /**
     * @param Language $language
     * @param Batch|null $batch
     * @return void
     * @throws ExportTranslationException
     */
    public function exportModelTranslationForLanguage(Language $language, null|Batch $batch = null): void
    {
        if ($batch) {
            $this->batch = $batch;
        }
        try {
            DB::beginTransaction();
            Translation::query()
                ->select('id', 'namespace', 'group', 'key', 'value')
                ->where('language_id', $language->id)
                ->where('type', '=', 'model')
                ->isUpdated(false)
                ->approved()
                ->exported(false)
                ->chunkById(200, function ($translations) use ($language) {
                    foreach ($translations as $translation) {
                        if ($this->batch) {
                            $this->batch->add(new ExportUpdatedModelTranslation($translation->id, $language->code));
                        } else {
                            $this->updateModelTranslation($translation, $language->code);
                        }
                    }
                });
            DB::commit();
        } catch(\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
