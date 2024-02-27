<?php

namespace Riomigal\Languages\Jobs;

use Riomigal\Languages\Jobs\Job\BaseJob;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Services\Traits\CanCreateTranslation;

class MassCreateEloquentTranslationsJob extends BaseJob
{
    use CanCreateTranslation;
    public function __construct(
        protected array  $translations,
        protected int $languageId,
        protected string $fromLanguageId
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
        $this->massCreateEloquentTranslations($this->translations, Language::find($this->languageId), Language::find($this->fromLanguageId));
    }
}
