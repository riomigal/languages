<?php

namespace Riomigal\Languages\Services\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Riomigal\Languages\Exceptions\ExportFileException;
use Riomigal\Languages\Models\Translation;

trait CanExportTranslation
{
    /**
     * @param string $type
     * @param string $languageCode
     * @param bool $isVendor
     * @param string $namespace
     * @param string $group
     * @param bool $forceExportAll
     * @return void
     * @throws ExportFileException
     */
    protected function updateTranslation(string $type, string $languageCode, bool $isVendor, string $namespace = '', string $group = '', bool $forceExportAll = false): void
    {
        $path = null;
        try {
            $query = Translation::where([
                ['type', '=', $type ],
                ['is_vendor', '=', $isVendor],
                ['namespace', '=', $namespace ],
                ['group', '=', $group ],
                ['language_code', '=', $languageCode],
            ])
                ->isUpdated(false)
                ->approved()
                ->when(!$forceExportAll, function($query) {
                    $query->exported(false);
                });

            /** @var array<array-key, string|null> $translations Stored values indexed by translation keys. */
            $translations = $query
                ->pluck('value', 'key')->all();

            if($type == 'json') {
                $relativePath = $languageCode;
            } else {
                $relativePath  = $languageCode . '/' . $group;
            }

            if($isVendor) {
                $path = App::langPath('vendor/' . $namespace . '/' . $relativePath . '.' . $type);
            } else {
                $path = App::langPath($relativePath . '.' . $type);
            }

            $this->updateFileContent($translations, $path, $type);
            $query->update(['exported' => true]);

        } catch (\Exception $e) {
            $errorId = Str::random();
            Log::error('CanExportTranslation::updateTranslation()', [
                'errorId' => $errorId,
                'errorMessage' => $e->getMessage()
            ]);
            $query->update(['exported' => false]);
            throw new ExportFileException($e->getMessage(), __('languages::exceptions.export_file_error', ['relativePathname' => $path, 'errorId' => $errorId]), 0);
        }
    }

    /**
     * Update the content of the file
     *
     * @param array<array-key, string|null> $translations
     * @param string $fullPath
     * @param string $type
     * @return void
     */
    protected function updateFileContent(array $translations, string $fullPath, string $type): void
    {
        if (!in_array($type, ['json', 'php'])) {
            Log::error('Invalid file extension. Extension must be php or json. ' . $type . ' given. Please check your language folder and rename the extension of this file ' . $fullPath . '.');
        }


        if (!File::exists($fullPath)) {
            $content = [];
            $directory = File::dirname($fullPath);
            if (!File::isDirectory($directory)) {
                File::makeDirectory($directory, 0755, true);
            }
            File::put($fullPath, " ");
        } else {
            if ($type == 'php') {
                $content = File::getRequire($fullPath);
            } else {
                $content = json_decode(File::get($fullPath), true);
            }
        }
        if(!is_array($content)) {
            $content = [];
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

        // file_put_contents previously wrote an empty string when JSON encoding failed.
        File::put($fullPath, $content === false ? '' : $content);
    }


    protected function updateModelTranslation(Translation $translation, string $languageCode): void
    {
        $modelInstance = app($translation->namespace);
        if (!$modelInstance instanceof Model) {
            throw new \TypeError('Translation namespace must resolve to an Eloquent model.');
        }
        $tableId = $modelInstance->getKeyName();
        $modelQuery = DB::table($modelInstance->getTable())->where($tableId, $translation->key);
        $model = $modelQuery->first();
        $column = $translation->group;
        // A null property name was coerced to an empty string by PHP.
        $data = $model->{$column ?? ''};
        if (is_string($data)) $data = json_decode($data, true);
        if (is_object($data)) $data = (array)$data;
        if ($data !== null && $data !== false && !is_array($data)) {
            throw new \TypeError('Model translation data must be an array, false or null.');
        }
        // PHP already promotes null and false to an array on offset assignment.
        if ($data === false) {
            $data = [];
        }
        $data[$languageCode] = $translation->value;
        $modelQuery->update([
            $column => json_encode($data)
        ]);
        $translation->update([
            'exported' => true,
        ]);
    }
}
