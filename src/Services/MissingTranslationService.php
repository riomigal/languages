<?php

namespace Riomigal\Languages\Services;

use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
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
    public function findMissingTranslations(null|Batch $batch = null): int
    {
        if ($batch) {
            $this->batch = $batch;
        }
        $this->languages = Language::all();

        foreach($this->languages as $language) {
            if ($this->batch) {
                $this->batch->add(new FindMissingTranslationsByLanguage($this->languages->pluck('id')->toArray(), $language->id));
            } else {
                $this->findMissingTranslationsByLanguage($this->languages, $language);
            }
        }

        return $this->translationsFound;
    }
}
