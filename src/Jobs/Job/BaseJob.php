<?php

namespace Riomigal\Languages\Jobs\Job;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Riomigal\Languages\Jobs\Traits\HandlesFailedJobs;

abstract class BaseJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandlesFailedJobs;

    public function __construct()
    {
        /** @var string|\UnitEnum|null $queue Configured queue name. */
        $queue = config('languages.queue_name');
        $this->onQueue($queue);
    }

    /**
     * @return void
     * @throws \Exception
     */
    abstract public function handle(): void;
}
