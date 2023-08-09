<?php

namespace Riomigal\Languages;

use Illuminate\Translation\FileLoader;
use Riomigal\Languages\Models\Setting;
use Riomigal\Languages\Models\Translation;

class TranslationLoader extends FileLoader
{
    /**
     * Loads the translations from DB
     *
     * @param string $locale
     * @param string $group
     * @param string|null $namespace
     *
     * @return array
     */
    public function load($locale,  $group, $namespace = null): array
    {
        if(!Setting::getCached()->import_vendor && $namespace && $namespace !== '*') {
            return parent::load($locale, $group, $namespace);
        }
        return Translation::getCachedTranslations($locale,  $group, $namespace);
    }
}
