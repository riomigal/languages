<?php

namespace Riomigal\Languages\Jobs;

use Riomigal\Languages\Jobs\Job\BaseJob;
use Riomigal\Languages\Services\Traits\CanCreateTranslation;

class MassCreateTranslationsJob extends BaseJob
{
    use CanCreateTranslation;
    public function __construct(
        protected array  $content,
        protected string $sharedPathname,
        protected string $type,
        protected int    $languageId,
        protected string $languageCode,
        protected string $namespace,
        protected string $group,
        protected bool $isVendor
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
        $this->massCreateTranslations($this->content, $this->sharedPathname, $this->type, $this->languageId, $this->languageCode, $this->namespace, $this->group, $this->isVendor);
    }
}
