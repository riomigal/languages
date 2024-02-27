<?php

namespace Riomigal\Languages\Services;

use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Collection;
use Riomigal\Languages\Jobs\FindMissingTranslationsByLanguage;
use Riomigal\Languages\Models\Language;
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
    /**
     * @param Batch|null $batch
     * @param Language|null $singleLanguage - Pass this parameter if you want only run through a single language
     * @return int
     * @throws \Riomigal\Languages\Exceptions\MassCreateTranslationsException
     */
    public function findMissingTranslations(null|Batch $batch = null, Language|null $singleLanguage = null): int
    {
        if ($batch) {
            $this->batch = $batch;
        }
        $this->languages = Language::all();

        $language = $this->languages->where('code', config('app.locale') ?? 'en')->first();

        if($singleLanguage) {
           $this->languages = $this->languages->filter(fn(Language $filteredLanguage) => $singleLanguage->id === $filteredLanguage->id);
        }

        if ($this->batch) {
            $this->batch->add(new FindMissingTranslationsByLanguage($this->languages->pluck('id')->toArray(), $language->id));
        } else {
            $this->findMissingTranslationsByLanguage($this->languages, $language);
        }

        return $this->translationsFound;
    }
}
