<?php

namespace Riomigal\Languages\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Riomigal\Languages\Jobs\Traits\HandlesFailedJobs;
use Riomigal\Languages\Services\Traits\CanCreateTranslation;

class MassCreateEloquentTranslationsJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, CanCreateTranslation, HandlesFailedJobs;

    public function __construct(
        protected array  $translations,
        protected int $languageId,
        protected string $languageCode,
        protected string $fromLanguageCode
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
        $this->massCreateEloquentTranslations($this->translations, $this->languageId, $this->languageCode, $this->fromLanguageCode);
    }
}
