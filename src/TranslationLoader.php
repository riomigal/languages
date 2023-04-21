<?php

namespace Riomigal\Languages;

use Illuminate\Support\Facades\Cache;
use Illuminate\Translation\FileLoader;
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
        return Translation::getCachedTranslations($locale,  $group, $namespace);
    }
}
