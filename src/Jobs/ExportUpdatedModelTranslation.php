<?php

namespace Riomigal\Languages\Jobs;

use Riomigal\Languages\Jobs\Job\BaseJob;
use Riomigal\Languages\Models\Translation;
use Riomigal\Languages\Services\Traits\CanExportTranslation;

class ExportUpdatedModelTranslation extends BaseJob
{
    use CanExportTranslation;

    public function __construct(
        protected int $translation_id,
        protected string $languageCode
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
        $translation = Translation::query()->findOrFail($this->translation_id);
        $this->updateModelTranslation($translation, $this->languageCode);
    }

}
