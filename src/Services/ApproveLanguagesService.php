<?php

namespace Riomigal\Languages\Services;

use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Translation;

class ApproveLanguagesService
{
    public function approveLanguages(Language $language, int $authUserId): void {

        $language->translations()->where('approved', false)
            ->chunkById(100, function($translations) use ($authUserId) {
                Translation::query()->whereIn('id', $translations->pluck('id')->all())->update($this->approvedTranslationUpdateArray($authUserId));
                foreach ($translations as $translation) {
                    $this->resetTranslationCache($translation);
                }
            });
    }

    public function approvedTranslationUpdateArray(int $authUserId): array
    {
        return [
            'approved' => true,
            'updated_translation' => false,
            'needs_translation' => false,
            'old_value' => null,
            'approved_by' => $authUserId,
            'previous_updated_by' => null,
            'previous_approved_by' => null,
        ];
    }

    public function resetTranslationCache(Translation $translation): void
    {
        Translation::unsetCachedTranslation($translation->language_code, $translation->group ?? null, $translation->namespace ?? null);
//        Translation::getCachedTranslations($translation->language_code, $translation->group ?? null, $translation->namespace ?? null);
    }
}
