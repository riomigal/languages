<?php

namespace Riomigal\Languages\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Riomigal\Languages\Models\Language;

class ImportLanguageService
{
    /**
     * Imports the languages
     *
     * @return void
     */
    public function importLanguages(): void
    {
        $connection = config('languages.db_connection');
        if ($connection !== null && !is_string($connection) && !$connection instanceof \UnitEnum) {
            throw new \TypeError('languages.db_connection must be a string, UnitEnum or null.');
        }
        DB::connection($connection)->transaction(function () {
            /** @var list<string> $directoryPaths Filesystem::directories() returns directory pathnames. */
            $directoryPaths = File::directories(App::langPath());
            $directories = array_map('basename', $directoryPaths);
            /** @var list<string> $languageCodes */
            $languageCodes = Language::pluck('code')->all();
            $directories = array_diff($directories, $languageCodes);
            if ($directories) {
                $languageArray = collect(Language::LANGUAGES)->whereIn('code', $directories)
                    ->map(function($language) {
                    $language['created_at'] = now();
                    $language['updated_at'] = now();
                    return $language;
                })->toArray();

                if ($languageArray) Language::insert($languageArray);
            }
        });
    }
}
