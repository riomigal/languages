<?php

namespace Riomigal\Languages\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Riomigal\Languages\Jobs\Traits\HandlesFailedJobs;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Services\Traits\CanCreateTranslation;

class FindMissingTranslationsByLanguage implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, CanCreateTranslation, HandlesFailedJobs;

    public function __construct(
        protected array $languageIds,
        protected int $languageId,
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
        $this->findMissingTranslationsByLanguage(Language::query()->whereIn('id', $this->languageIds)->get(), Language::find($this->languageId));
    }
}
