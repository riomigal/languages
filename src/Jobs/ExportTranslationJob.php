<?php

namespace Riomigal\Languages\Jobs;

use Riomigal\Languages\Jobs\Job\BaseJob;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Services\ExportTranslationService;

class ExportTranslationJob extends BaseJob
{
    public function __construct(
        protected Language                 $language,
        protected bool $exportOnlyModels = false
    )
    {
        parent::__construct();
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function handle(): void
    {
        resolve(ExportTranslationService::class)->exportTranslationForLanguage($this->language, $this->batch(), $this->exportOnlyModels);
    }

}
