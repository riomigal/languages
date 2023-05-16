<?php

namespace Riomigal\Languages\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Riomigal\Languages\Jobs\Traits\HandlesFailedJobs;
use Riomigal\Languages\Services\Traits\CanExportTranslation;

class ExportUpdatedTranslation implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, CanExportTranslation, HandlesFailedJobs;

    public function __construct(
        protected string $type,
        protected string $languageCode,
        protected bool $isVendor,
        protected string $namespace = '',
        protected string $group = ''
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
        $this->updateTranslation($this->type, $this->languageCode, $this->isVendor, $this->namespace, $this->group);
    }

}
