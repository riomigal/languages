<?php

namespace Riomigal\Languages\Jobs;

use Riomigal\Languages\Jobs\Job\BaseJob;
use Riomigal\Languages\Services\MissingTranslationService;

class FindMissingTranslationsJob extends BaseJob
{
    /**
     * @return void
     * @throws \Riomigal\Languages\Exceptions\MassCreateTranslationsException
     */
    public function handle(): void
    {
        resolve(MissingTranslationService::class)->findMissingTranslations($this->batch());
    }
}
