<?php

namespace Riomigal\Languages\Tests\Traits;

use Riomigal\Languages\Models\Language;

trait CreateLanguages
{
    /**
     * @param string|null $code
     * @return Language
     */
    public function createFallbackLanguage(string $code = null): Language
    {
        if (!$code) $code = config('app.fallback_locale');
        $language = collect(Language::LANGUAGES)->where('code', $code)->first();

        return factory(Language::class)->create([
            'name' => $language['name'],
            'native_name' => $language['native_name'],
            'code' => $language['code'],
        ]);
    }

}
