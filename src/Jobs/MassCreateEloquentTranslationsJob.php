<?php

namespace Riomigal\Languages\Jobs;

use Riomigal\Languages\Jobs\Job\BaseJob;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Services\Traits\CanCreateTranslation;

class MassCreateEloquentTranslationsJob extends BaseJob
{
    use CanCreateTranslation;

    public function __construct(
        protected array  $translationIds,
        protected int $languageId,
        protected string $fromLanguageId
    )
    {
        $this->timeout = 300;
        $this->queue = config('languages.queue_name');
        parent::__construct();
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function handle(): void
    {
        $this->massCreateEloquentTranslations($this->translationIds, Language::find($this->languageId), Language::find($this->fromLanguageId));
    }
}
