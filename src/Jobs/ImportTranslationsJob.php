<?php

namespace Riomigal\Languages\Jobs;

use Riomigal\Languages\Jobs\Job\BaseJob;
use Riomigal\Languages\Services\ImportTranslationService;

class ImportTranslationsJob extends BaseJob
{
    public function __construct(
        protected ImportTranslationService $importTranslationService,
    )
    {

    }

    /**
     * @return void
     * @throws \Exception
     */
    public function handle(): void
    {
        $this->importTranslationService->importTranslations($this->batch());
    }
}
