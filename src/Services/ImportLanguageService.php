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
        DB::connection(config('languages.db_connection'))->transaction(function () {
            $directories = array_map('basename', File::directories(App::langPath()));
            $directories = array_diff($directories, Language::pluck('code')->all());
            if ($directories) {
                $languageArray = collect(Language::LANGUAGES)->whereIn('code', $directories)->toArray();
                if ($languageArray) Language::insert($languageArray);
            }
        });
    }
}
