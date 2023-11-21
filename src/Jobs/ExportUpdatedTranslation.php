<?php

namespace Riomigal\Languages\Jobs;

use Riomigal\Languages\Jobs\Job\BaseJob;
use Riomigal\Languages\Services\Traits\CanExportTranslation;

class ExportUpdatedTranslation extends BaseJob
{
    use CanExportTranslation;

    public function __construct(
        protected string $type,
        protected string $languageCode,
        protected bool $isVendor,
        protected string $namespace = '',
        protected string $group = ''
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
        $this->updateTranslation($this->type, $this->languageCode, $this->isVendor, $this->namespace, $this->group);
    }

}
