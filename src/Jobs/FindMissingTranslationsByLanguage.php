<?php

namespace Riomigal\Languages\Jobs;

use Riomigal\Languages\Jobs\Job\BaseJob;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Services\Traits\CanCreateTranslation;

class FindMissingTranslationsByLanguage extends BaseJob
{
    use CanCreateTranslation;

    public function __construct(
        protected array $languageIds,
        protected int $languageId,
    )
    {
        parent::__construct();
    }

    /**
     * @return void
     * @throws \Riomigal\Languages\Exceptions\MassCreateTranslationsException
     */
    public function handle(): void
    {
        $this->findMissingTranslationsByLanguage(Language::query()->whereIn('id', $this->languageIds)->get(), Language::find($this->languageId), $this->batch());
    }
}
