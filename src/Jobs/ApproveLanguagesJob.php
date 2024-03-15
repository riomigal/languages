<?php

namespace Riomigal\Languages\Jobs;

use Riomigal\Languages\Jobs\Job\BaseJob;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Services\ApproveLanguagesService;

class ApproveLanguagesJob extends BaseJob
{
    public function __construct(
        protected Language                 $language,
        protected int $authUserId
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
        resolve(ApproveLanguagesService::class)->approveLanguages($this->language, $this->authUserId);
    }

}
