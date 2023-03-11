<?php

namespace Riomigal\Languages\Jobs;

use Riomigal\Languages\Jobs\Job\BaseJob;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Services\ExportTranslationService;

class ExportTranslationJob extends BaseJob
{
    public function __construct(
        protected ExportTranslationService $exportTranslationService,
        protected Language                 $language,
    )
    {
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function handle(): void
    {
        $this->exportTranslationService->exportTranslationForLanguage($this->language, $this->batch());
    }

}
