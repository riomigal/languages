<?php

namespace Riomigal\Languages\Jobs;

use Illuminate\Database\Eloquent\Collection;
use Riomigal\Languages\Jobs\ExportTranslationJob;
use Riomigal\Languages\Jobs\Job\BaseJob;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Services\ExportTranslationService;

class ExportTranslationsJob extends BaseJob
{
    public function __construct(
        protected ExportTranslationService $exportTranslationService,
        protected Collection               $languages,
    )
    {

    }

    /**
     * @return void
     * @throws \Exception
     */
    public function handle(): void
    {
        $this->languages->each(function (Language $language) {
            $this->batch()->add(new ExportTranslationJob($this->exportTranslationService, $language));
        });
    }
}
