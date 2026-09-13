<?php

namespace Riomigal\Languages\Jobs;

use Riomigal\Languages\Jobs\Job\BaseJob;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Translation;
use Riomigal\Languages\Models\Translator;
use Riomigal\Languages\Services\OpenAITranslationService;

class UpdateTranslationJob extends BaseJob
{


    /**
     * @param Translator|null $authUser Validated before applying the update.
     */
    public function __construct(
        protected Language $rootLanguage,
        protected Translation $translation,
        protected string $translatedValue,
        public ?Translator $authUser
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
        $authUser = $this->authUser ?? throw new \DomainException('Updating a translation requires an acting translator.');
        $this->translation->exported = false;
        $this->translation->needs_translation = false;
        if(!$this->translation->updated_translation) {
            $this->translation->previous_approved_by = $this->translation->approved_by;
            $this->translation->previous_updated_by = $this->translation->updated_by;
            $this->translation->old_value = $this->translation->value;
        }
        $this->translation->approved_by = null;
        $this->translation->updated_by = $authUser->id;
        $this->translation->updated_translation = true;
        $this->translation->value =  resolve(OpenAITranslationService::class)->translateString($this->rootLanguage, $this->translation->language, $this->translatedValue);
        $this->translation->approved = false;
        $this->translation->save();
    }

}
