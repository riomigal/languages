<?php

namespace Riomigal\Languages\Services\Traits;

use Illuminate\Support\Facades\Log;
use Riomigal\Languages\Exceptions\ExportFileException;
use Riomigal\Languages\Exceptions\ExportTranslationException;
use Riomigal\Languages\Models\Translation;

trait CanExportTranslation
{
    /**
     * @param string $relativePathname
     * @param string $type
     * @param int $languageId
     * @return void
     * @throws ExportTranslationException
     */
    protected function updateTranslation(string $relativePathname, string $type, int $languageId): void
    {
        try {
            $translations = Translation::where('relative_pathname', $relativePathname)
                ->isUpdated()
                ->approved()
                ->pluck('value', 'key')->all();


            $this->updateFileContent($translations, base_path(config('languages.language_folder_folder_directory') . '/' . $relativePathname), $type);


            Translation::where('language_id', $languageId)
                ->where('relative_pathname', $relativePathname)
                ->isUpdated()
                ->approved()
                ->update(['updated_translation' => false]);
        } catch (\Exception $e) {
            throw new ExportFileException($e->getMessage(), __('languages::exceptions.export_file_error', ['relativePathname' => $relativePathname]), 0, $e);
        }
    }

    /**
     * Update the content of the file
     *
     * @param array $translations
     * @param string $fullPath
     * @param string $type
     * @return void
     * @throws ExportTranslationException
     */
    protected function updateFileContent(array $translations, string $fullPath, string $type): void
    {
        if (!in_array($type, ['json', 'php'])) {
            Log::error('Invalid file extension. Extension must be php or json. ' . $type . ' given. Please check your language folder and rename the extension of this file ' . $fullPath . '.');
        }

        if (!file_exists($fullPath)) {
            $content = [];
            $directory = dirname($fullPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            file_put_contents($fullPath, " ");
        } else {
            if ($type == 'php') {
                $content = require($fullPath);
            } else {
                $content = json_decode(file_get_contents($fullPath), true);
            }
        }

        foreach ($translations as $key => $value) {
            $content[$key] = $value;
        }

        if ($type == 'php') {
            $content = "<?php\n" .
                "return " .
                var_export($content, true) .
                ";";
        } else {
            $content = json_encode($content);
        }

        file_put_contents($fullPath, $content);
    }
}
