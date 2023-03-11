<?php

namespace Riomigal\Languages\Tests\Traits;

use Illuminate\Database\Eloquent\Collection;
use Riomigal\Languages\Models\Translator;

trait CreateUsers
{
    /**
     * @param Collection $languages
     * @param array $params
     * @return Translator
     */
    public function createAdmin(Collection $languages, array $params = []): Translator
    {
        $translator = $this->createUser($languages, $params);
        $translator->admin = true;
        $translator->save();
        return $translator;
    }

    /**
     * @param Collection $languages
     * @param array $params
     * @return Translator
     */
    public function createUser(Collection $languages, array $params = []): Translator
    {
        $params['admin'] = false;
        $translator = $this->createTranslator($params);
        $translator->languages()->detach();
        $translator->languages()->attach($languages->pluck('id')->all());
        return $translator;
    }

    /**
     * @param array $params
     * @return Translator
     */
    protected function createTranslator(array $params = []): Translator
    {
        return factory(Translator::class)->create($params);
    }

}
