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

    protected null|Batch $batch = null;

    protected string $root;

    protected string $namespace = '';

    protected Collection $languages;

    protected Language $language;

    protected \SplFileInfo $file;
    protected bool $isVendor = false;
    protected string $languagePlaceholder = '{language}';

    /**
     * @throws ImportTranslationsException
     */
    public function importTranslations(null|Batch $batch = null): void
    {
        if ($batch) {
            $this->batch = $batch;
        }

        $this->languages = Language::query()
            ->when(Setting::getCached()->import_only_from_root_language, function($query) {
                $query->where('code', config('app.locale'));
            })->get();

        $this->createMissingDirectory(App::langPath());
        $this->createMissingDirectory(App::langPath('vendor'));

        $this->root = App::langPath();
        $this->importFromRoot();

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
                            $this->massCreateTranslations($content[$languageCode],  'model', $language->id, $language->code, $this->namespace,  $modelClass, $this->isVendor);

                            //Inefficient can be removed
//                            $this->batch->add(new MassCreateTranslationsJob($content[$languageCode],'model', $language->id, $language->code, $this->namespace,  $modelClass, $this->isVendor));
                        } else {
                            $this->massCreateTranslations($content[$languageCode],  'model', $language->id, $language->code, $this->namespace,  $modelClass, $this->isVendor);
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
     * @throws ImportTranslationsException
     */
    protected function generateContent(string $root, \SplFileInfo $file): void
    {
        try {
            $languageHelper = resolve(LanguageHelper::class);
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
                } else {
                    $offset = strlen($this->language->code) + 2;
                    $group = str_replace('.' . $type, '', substr($relativePathname, $offset));
                    $content = require($file->getRealPath());
                }

                if (!is_array($content)) {
                    if ($type == 'php') {
                        Log::error('File (' . $relativePathname . ') has no valid php array, Please check the file in the filesystem.');
                    } else {
                        Log::error('File (' . $relativePathname . ') has no valid JSON string, Please check the file in the filesystem.');
                    }
                }

                if (count($content) > 0) {
                    $content = $languageHelper->array_convert_keys_to_dot_notation($content);
                    if ($this->batch) {
                        $this->massCreateTranslations($content, $type, $this->language->id, $this->language->code, $this->namespace, $group, $this->isVendor);
                        // Inefficient can be removed
//                        $this->batch->add(new MassCreateTranslationsJob($content, $type, $this->language->id, $this->language->code, $this->namespace, $group, $this->isVendor));
                    } else {
                        $this->massCreateTranslations($content, $type, $this->language->id, $this->language->code, $this->namespace, $group, $this->isVendor);
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
