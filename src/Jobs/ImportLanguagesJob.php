<?php

namespace Riomigal\Languages\Jobs;

use Riomigal\Languages\Jobs\Job\BaseJob;
use Riomigal\Languages\Services\ImportLanguageService;

class ImportLanguagesJob extends BaseJob
{
    /**
     * @return void
     * @throws \Exception
     */
    public function handle(): void
    {
        resolve(ImportLanguageService::class)->importLanguages();
    }
}
