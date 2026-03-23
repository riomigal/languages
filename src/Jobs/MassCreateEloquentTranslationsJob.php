<?php

namespace Riomigal\Languages\Jobs;

use Riomigal\Languages\Jobs\Job\BaseJob;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Services\Traits\CanCreateTranslation;

class MassCreateEloquentTranslationsJob extends BaseJob
{
    use CanCreateTranslation;

    public int $timeout = 300;

    public function __construct(
        protected array  $translationIds,
        protected int $languageId,
        protected int $fromLanguageId
    )
    {
        $this->queue = config('languages.queue_name');
        parent::__construct();
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function handle(): void
    {
        $this->massCreateEloquentTranslations(
            $this->translationIds,
            Language::query()->findOrFail($this->languageId),
            Language::query()->findOrFail($this->fromLanguageId)
        );
    }
}
