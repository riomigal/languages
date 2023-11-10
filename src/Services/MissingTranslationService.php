<?php

namespace Riomigal\Languages\Services;

use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Collection;
use Riomigal\Languages\Jobs\MassCreateEloquentTranslationsJob;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Translation;
use Riomigal\Languages\Services\Traits\CanCreateTranslation;

class MissingTranslationService
{
    use CanCreateTranslation;

    /**
     * @var Collection
     */
    protected Collection $missingLanguages;

    /**
     * @var Collection
     */
    protected Collection $allLanguages;

    /**
     * @var int
     */
    protected int $translationsFound = 0;

    /**
     * @var null|Batch
     */
    protected null|Batch $batch = null;

    /**
     * @param null|Batch $batch
     * @return int
     */
    public function findMissingTranslations(null|Batch $batch = null): int
    {
        if ($batch) {
            $this->batch = $batch;
        }
        $this->allLanguages = Language::all();


//        $es = Translation::where('language_code', 'it')->pluck('shared_identifier')->all();
//        $en = Translation::where('language_code', 'en')->pluck('shared_identifier')->all();
//        $result = array_diff($es, $en);
//        dd($result);

        foreach($this->allLanguages as $allLanguage) {

            $this->missingLanguages = $this->allLanguages->reject(fn($language) => $language->id == $allLanguage->id);

            Translation::query()->select('shared_identifier')->where('language_code', $allLanguage->code)
                ->groupBy('shared_identifier')
                ->orderBy('shared_identifier')->chunk(400,
                    function ($records) use ($allLanguage) {
                        foreach ($this->missingLanguages as $language) {

                            // Get array of all identifier
                            $identifierArray = $records->pluck('shared_identifier')->all();

                            // Get array of language identifier found
                            $identifierArrayTwo = Translation::query()->select('shared_identifier')
                                ->where('language_id', $language->id)
                                ->whereIn('shared_identifier', $identifierArray)->pluck('shared_identifier')->all();

                            // Get missing identifier for language
                            $missingIdentifier = array_diff($identifierArray, $identifierArrayTwo);

                            Translation::query()
                                ->select('shared_identifier', 'namespace', 'group', 'is_vendor', 'type', 'key', 'value')
                                ->whereIn('shared_identifier', $missingIdentifier)
                                ->where('language_code', $allLanguage->code)
                                ->groupBy('shared_identifier', 'namespace', 'group', 'is_vendor', 'type', 'key', 'value')
                                ->orderBy('shared_identifier')
                                ->chunk(400, function ($translations) use ($language) {
//                                    if ($this->batch) {
//                                        $this->batch->add(new MassCreateEloquentTranslationsJob($translations->toArray(), $language->id, $language->code));
//                                    } else {
                                        $this->massCreateEloquentTranslations($translations->toArray(), $language->id, $language->code);
//                                    }
                                });
                        }
                    }
                );
        }

        return $this->translationsFound;
    }
}
