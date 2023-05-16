<?php

namespace Riomigal\Languages\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Riomigal\Languages\Jobs\Traits\HandlesFailedJobs;
use Riomigal\Languages\Services\Traits\CanCreateTranslation;

class MassCreateTranslationsJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, CanCreateTranslation, HandlesFailedJobs;

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
        $this->onQueue(config('languages.queue_name'));
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
