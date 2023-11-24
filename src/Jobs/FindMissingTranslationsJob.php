<?php

namespace Riomigal\Languages\Jobs;

use Riomigal\Languages\Jobs\Job\BaseJob;
use Riomigal\Languages\Services\MissingTranslationService;

class FindMissingTranslationsJob extends BaseJob
{
    /**
     * @return void
     */
    public function handle(): void
    {
        resolve(MissingTranslationService::class)->findMissingTranslations($this->batch());
    }
}
