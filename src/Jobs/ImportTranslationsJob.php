<?php

namespace Riomigal\Languages\Jobs;

use Riomigal\Languages\Jobs\Job\BaseJob;
use Riomigal\Languages\Services\ImportTranslationService;

class ImportTranslationsJob extends BaseJob
{
    /**
     * @return void
     * @throws \Exception
     */
    public function handle(): void
    {
        resolve(ImportTranslationService::class)->importTranslations($this->batch());
    }
}
