<?php

namespace Riomigal\Languages\Services;

use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Riomigal\Languages\Exceptions\ImportTranslationsException;
use Riomigal\Languages\Jobs\MassCreateTranslationsJob;
use Riomigal\Languages\Models\Language;
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
    protected string $languagePlaceholder = '{language}';

    /**
     * Imports translations
     *
     * @param Batch|null $batch
     * @return void
     * @throws ImportTranslationsException
     */
    public function importTranslations(null|Batch $batch): void
    {
        if ($batch) {
            $this->batch = $batch;
        }

        $languages = Language::query()->get();

        // Imports app translations
        $root = base_path(config('languages.language_folder_folder_directory'));
        $this->importFromRoot(base_path(config('languages.language_folder_folder_directory')), $languages);

        // Imports vendor translations if language exists in db
        $vendorPath = $root . '/' . config('languages.language_vendor_folder_directory');
        foreach (File::directories($vendorPath) as $directory) {
            $this->importFromRoot($directory, $languages);
        };

    }

    /**
     * @param string $root
     * @param Collection $languages
     * @return void
     * @throws ImportTranslationsException
     */
    protected function importFromRoot(string $root, Collection $languages): void
    {
        $rootJsonFiles = collect(File::files($root))->mapWithKeys(function (SplFileInfo $file) {
            return [$file->getFilename() => $file];
        })->toArray();

        foreach ($languages as $language) {

            if (!file_exists($root . '/' . $language->code)) {
                mkdir($root . '/' . $language->code);
            }
            // Handles JSON Language file in root directory
            if (isset($rootJsonFiles[$language->code . '.json'])) {
                $this->generateContent($rootJsonFiles[$language->code . '.json'], $language);
            }

            // Handles files in language subdirectory
            foreach (File::allFiles($root . '/' . $language->code) as $file) {
                $this->generateContent($file, $language);
            }
        }
    }

    /**
     * @param \SplFileInfo $file
     * @param Language $language
     * @return void
     * @throws ImportTranslationsException
     */
    protected function generateContent(\SplFileInfo $file, Language $language): void
    {
        try {
            $relativePathname = str_replace(base_path(config('languages.language_folder_folder_directory')), '', $file->getRealPath());
            $relativePath = File::dirname($relativePathname);
            $type = $file->getExtension();
            if (!in_array($type, ['json', 'php'])) {
                Log::error('File (' . $relativePathname . ') has an invalid file extension. File extension must be php or json. Remove the file or change the extension.');
            }

            if ($type == 'json') {
                $content = json_decode(file_get_contents($file->getRealPath()), true);
                $sharedRelativePathname = str_replace($language->code . '.json', $this->languagePlaceholder . '.json', $relativePathname);
            } else {
                $content = require($file->getRealPath());
                $sharedRelativePathname = $this->languagePlaceholder . substr($relativePathname, 2);
            }

            if (!is_array($content)) {
                if ($type == 'php') {
                    Log::error('File (' . $relativePathname . ') has no valid php array, Please check the file in the filesystem.');
                } else {
                    Log::error('File (' . $relativePathname . ') has no valid JSON string, Please check the file in the filesystem.');
                }
            }

            if (count($content) > 0) {
                $content = app('lang.helper')->array_convert_keys_to_dot_notation($content);
                if ($this->batch) {
                    $this->batch->add(new MassCreateTranslationsJob($content, $relativePath, $relativePathname, $sharedRelativePathname, $type, $language->id, $language->code));
                } else {
                    MassCreateTranslationsJob::dispatch($content, $relativePath, $relativePathname, $sharedRelativePathname, $type, $language->id, $language->code);
                }
            }
        } catch (\Throwable $e) {
            throw new ImportTranslationsException($e->getMessage(), __('languages::exceptions.invalid_file_error', ['relativePathname' => $relativePathname]), 0, $e);
        }
    }

}
