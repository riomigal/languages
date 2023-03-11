<?php

namespace Riomigal\Languages\Jobs;

use Riomigal\Languages\Jobs\Job\BaseJob;
use Riomigal\Languages\Services\MissingTranslationService;

class FindMissingTranslationsJob extends BaseJob
{
    public function __construct(
        protected MissingTranslationService $missingTranslationsService,
    )
    {
    }

    /**
     * @return void
     */
    public function handle(): void
    {
        $this->missingTranslationsService->findMissingTranslations($this->batch());
    }
}
