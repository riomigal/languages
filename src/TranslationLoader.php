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
     * @return array<array-key, mixed> External translation file contents or stored lines; numeric keys are allowed.
     */
    public function load($locale,  $group, $namespace = null): array
    {
        if(!Setting::getCached()->import_vendor && $namespace && $namespace !== '*') {
            /** @var array<array-key, mixed> $lines FileLoader returns arbitrary PHP/JSON translation file contents. */
            $lines = parent::load($locale, $group, $namespace);
            return $lines;
        }
        return Translation::getCachedTranslations($locale,  $group, $namespace);
    }
}
