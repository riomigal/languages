<?php

namespace Riomigal\Languages\Services;

use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Riomigal\Languages\Exceptions\ImportTranslationsException;
use Riomigal\Languages\Helpers\LanguageHelper;
use Riomigal\Languages\Jobs\MassCreateTranslationsJob;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Setting;
use Riomigal\Languages\Services\Traits\CanCreateTranslation;
use Symfony\Component\Finder\SplFileInfo;

class ImportTranslationService
{
    use CanCreateTranslation;

    /**
     * @var null|Batch
     */
    protected null|Batch $batch = null;

    /**
     * @var string
     */
    protected string $root;

    /**
     * @var string
     */
    protected string $namespace = '';

    /**
     * @var Collection
     */
    protected Collection $languages;

    /**
     * @var Language
     */
    protected Language $language;

    /**
     * @var \SplFileInfo
     */
    protected \SplFileInfo $file;

    /**
     * @var bool
     */
    protected bool $isVendor = false;

    /**
     * @var string
     */
    protected string $languagePlaceholder = '{language}';

    /**
     * Imports translations
     *
     * @param Batch|null $batch
     * @return void
     * @throws ImportTranslationsException
     */
    public function importTranslations(null|Batch $batch = null): void
    {
        if ($batch) {
            $this->batch = $batch;
        }

        // Get all languages
        $this->languages = Language::query()->get();

        // Create root lang and vendor directory if missing
        $this->createMissingDirectory(App::langPath());
        $this->createMissingDirectory(App::langPath('vendor'));

        // Imports app translations
        $this->root = App::langPath();
        $this->importFromRoot();

        // Imports vendor translations
        $this->importVendorTranslations();

        // Imports model translations
        $this->importModelTranslations();
    }

    protected function importModelTranslations(): void
    {
        $models = config('languages.translatable_models');

        foreach($models as $modelClass) {
            $modelInstance = app($modelClass);
            $tableId = $modelInstance->getKeyName();
            DB::table($modelInstance->getTable())->chunkById(300,
                function($models) use( $modelClass, $modelInstance, $tableId) {
                    $content = [];
                    foreach($this->languages as $language) {
                        $languageCode = $language->code;
                        $content[$languageCode] = [];
                        foreach($models as $model) {
                            if(!$modelInstance->translatable) continue;
                            foreach($modelInstance->translatable as $column) {
                                $data = $model->$column;
                                if(is_string($data)) $data = json_decode($data, true);
                                if(is_object($data)) $data = (array) $data;
                                if(isset($data[$languageCode])) {
                                    $content[$languageCode][$column . '.' . $model->$tableId] = $data[$languageCode];
                                }
                            }
                        }
                        if ($this->batch) {
                            $this->batch->add(new MassCreateTranslationsJob($content[$languageCode], '', 'model', $language->id, $language->code, $this->namespace,  $modelClass, $this->isVendor));
                        } else {
                            $this->massCreateTranslations($content[$languageCode], '', 'model', $language->id, $language->code, $this->namespace,  $modelClass, $this->isVendor);
                        }
                    }
                }
            );

        }
    }

    /**
     * @return void
     * @throws ImportTranslationsException
     */
    protected function importVendorTranslations(): void
    {
        if(Setting::getCached()->import_vendor) {
            $loader = Lang::getLoader();
            $this->isVendor = true;
            foreach ($loader->namespaces() as $namespace => $directory) {
                $this->namespace = $namespace;
                $publishedVendorDirectory = App::langPath('vendor/' . $this->namespace);
                $this->createMissingDirectory($publishedVendorDirectory);
                // Look first if there are published lang files
                $this->root = $publishedVendorDirectory;
                $this->importFromRoot();
                // Look for not published lang files
                $this->root = $directory;
                $this->importFromRoot();
            };
        }
    }


    /**
     * @return void
     * @throws ImportTranslationsException
     */
    protected function importFromRoot(): void
    {
        // Get files from root directory
        $rootJsonFiles = collect(File::files($this->root))->mapWithKeys(function (SplFileInfo $file) {
            return [$file->getFilename() => $file];
        })->toArray();

        // Loop through languages in directory
        foreach ($this->languages as $language) {
            $this->language = $language;
            if ($this->isVendor) {
                $this->createMissingDirectory(App::langPath('vendor/' . $this->namespace) . '/' . $this->language->code);
            } else {
                $this->createMissingDirectory($this->root . '/' . $this->language->code);
            }

            // Handles JSON Language file in root directory
            if (isset($rootJsonFiles[$this->language->code . '.json'])) {
                $this->generateContent($this->root, $rootJsonFiles[$this->language->code . '.json']);
            }

            // Handles files in language subdirectory
            if(File::exists($this->root . '/' . $this->language->code)) {
                foreach (File::allFiles($this->root . '/' . $this->language->code) as $file) {
                    $this->generateContent(str_replace('/src/../', '/', $this->root), $file);
                }
            }
        }
    }

    /**
     * @param string $root
     * @param \SplFileInfo $file
     * @return void
     * @throws ImportTranslationsException
     */
    protected function generateContent(string $root, \SplFileInfo $file): void
    {
        try {
            if(File::exists($file->getRealPath())) {
                $relativePathname = str_replace($root, '', $file->getRealPath());
                $relativePath = File::dirname($relativePathname);
                $type = $file->getExtension();
                if (!in_array($type, ['json', 'php'])) {
                    Log::error('File (' . $relativePathname . ') has an invalid file extension. File extension must be php or json. Remove the file or change the extension.');
                }

                if ($type == 'json') {
                    $group = '';
                    $content = json_decode(File::get($file->getRealPath()), true);
                    $sharedRelativePathname = str_replace($this->language->code . '.json', $this->languagePlaceholder . '.json', $relativePathname);
                } else {
                    $offset = strlen($this->language->code) + 2;
                    $group = str_replace('.' . $type, '', substr($relativePathname, $offset));
                    $content = require($file->getRealPath());
                    $sharedRelativePathname = $this->languagePlaceholder . substr($relativePathname, $offset);
                }

                if (!is_array($content)) {
                    if ($type == 'php') {
                        Log::error('File (' . $relativePathname . ') has no valid php array, Please check the file in the filesystem.');
                    } else {
                        Log::error('File (' . $relativePathname . ') has no valid JSON string, Please check the file in the filesystem.');
                    }
                }

                if (count($content) > 0) {
                    $content = resolve(LanguageHelper::class)->array_convert_keys_to_dot_notation($content);
                    if ($this->batch) {
                        $this->batch->add(new MassCreateTranslationsJob($content, $sharedRelativePathname, $type, $this->language->id, $this->language->code, $this->namespace, $group, $this->isVendor));
                    } else {
                        $this->massCreateTranslations($content, $sharedRelativePathname, $type, $this->language->id, $this->language->code, $this->namespace, $group, $this->isVendor);
                    }
                }
            }
        } catch (\Throwable $e) {
            throw new ImportTranslationsException($e->getMessage(), __('languages::exceptions.invalid_file_error', ['relativePathname' => $relativePathname]), 0);
        }
    }

    /**
     * @param string $path
     * @return void
     */
    protected function createMissingDirectory(string $path): void
    {
        if(!Setting::getCached()->db_loader) {
            if (!File::exists($path)) {
                File::makeDirectory($path);
            }
        }
    }

}
