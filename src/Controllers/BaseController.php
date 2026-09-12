<?php

namespace Riomigal\Languages\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Translation;
use Riomigal\Languages\Models\Translator;

abstract class BaseController extends Controller
{
    /**
     * @param Request $request
     * @return void
     */
    public function __construct(protected Request $request)
    {
    }

    /**
     * Return the translator resolved by EnsureTranslator.
     *
     * @return Translator
     */
    public function authUser(): Translator
    {
        $authUser = $this->request->attributes->get('authUser');

        if (!$authUser instanceof Translator) {
            abort(403);
        }

        return $authUser;
    }

    /**
     * @return bool
     */
    public function isAdministrator(): bool
    {
        return (bool) $this->authUser()->admin;
    }

    /**
     * Restrict a language query to the translator's assigned languages.
     *
     * @param Builder<Language> $query
     * @return Builder<Language>
     */
    protected function scopeLanguages(Builder $query): Builder
    {
        if (!$this->isAdministrator()) {
            /** @var string $table Configured language table name. */
            $table = config('languages.table_languages');
            $query->whereIn(
                $table . '.id',
                $this->authUser()->languages()->pluck($table . '.id')
            );
        }

        return $query;
    }

    /**
     * Resolve a translation only within a permitted language, otherwise deny access.
     *
     * @param int $id
     * @param Language $language
     * @return Translation
     */
    protected function resolveTranslation(int $id, Language $language): Translation
    {
        if (!$this->isAdministrator() && !$this->scopeLanguages(Language::query())->whereKey($language->getKey())->exists()) {
            abort(403);
        }

        $translation = $language->translations()->find($id);

        if (!$translation) {
            abort(403);
        }

        return $translation;
    }
}
