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
    protected Collection $languages;

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
    public function findMissingTranslations(null|Batch $batch): int
    {
        if ($batch) {
            $this->batch = $batch;
        }
        $this->languages = Language::query()->get();

        Translation::query()->select('shared_identifier')->groupBy('shared_identifier')->orderBy('shared_identifier')->chunk(400,
            function ($records) {
                foreach ($this->languages as $language) {
                    // Get array of all identifier
                    $identifierArray = $records->pluck('shared_identifier')->all();


                    // Get array of language identifier found
                    $identifierArrayTwo = Translation::query()->select('shared_identifier')
                        ->where('language_id', $language->id)
                        ->whereIn('shared_identifier', $identifierArray)->pluck('shared_identifier')->all();

                    // Get missing identifier for language
                    $missingIdentifier = array_diff($identifierArray, $identifierArrayTwo);

                    Translation::query()
                        ->select('shared_identifier', 'relative_path', 'relative_pathname', 'file', 'type', 'key')
                        ->whereIn('shared_identifier', $missingIdentifier)
                        ->groupBy('shared_identifier', 'relative_path', 'relative_pathname', 'file', 'type', 'key')
                        ->orderBy('shared_identifier')
                        ->chunk(400, function ($translations) use ($language) {
                            if ($this->batch) {
                                $this->batch->add(new MassCreateEloquentTranslationsJob($translations->toArray(), $language->id, $language->code));
                            } else {
                                MassCreateEloquentTranslationsJob::dispatch($translations->toArray(), $language->id, $language->code);
                            }
                        });
                }
            }
        );

        return $this->translationsFound;
    }
}
