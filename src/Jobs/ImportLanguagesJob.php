<?php

namespace Riomigal\Languages\Jobs;

use Riomigal\Languages\Jobs\Job\BaseJob;
use Riomigal\Languages\Services\ImportLanguageService;

class ImportLanguagesJob extends BaseJob
{

    public function __construct(
        protected ImportLanguageService $importLanguageService,

    )
    {
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function handle(): void
    {
        $this->importLanguageService->importLanguages();
    }
}
