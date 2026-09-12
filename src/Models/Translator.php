<?php

namespace Riomigal\Languages\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Riomigal\Languages\Notifications\FlashMessage;

/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string|null $phone
 * @property bool $admin
 * @property string|null $password
 * @property Collection<int, Language> $languages
 *
 * @mixin Builder<Translator>
 */
class Translator extends Authenticatable
{
    use Notifiable;

    /**
     * @var string
     */
    protected $guard = "translator";

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'admin',
        'email',
        'password',
        'phone'
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'admin' => 'boolean'
    ];

    /**
     * Create a new Eloquent model instance.
     *
     * @template TAttribute
     * @param array<string, TAttribute> $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $table = config('languages.table_translators');
        if ($table !== null && !is_string($table)) {
            throw new \TypeError('languages.table_translators must be a string or null.');
        }
        $this->table = $table;
        $connection = config('languages.db_connection');
        if ($connection !== null && !is_string($connection) && !$connection instanceof \UnitEnum) {
            throw new \TypeError('languages.db_connection must be a string, UnitEnum or null.');
        }
        $this->connection = $connection;
        parent::__construct($attributes);
    }

    /**
     * @return BelongsToMany<Language, $this>
     */
    public function languages(): BelongsToMany
    {
        $table = config('languages.table_translator_language');
        if ($table !== null && !is_string($table)) {
            throw new \TypeError('languages.table_translator_language must be a string or null.');
        }
        return $this->belongsToMany(Language::class, $table);
    }


    /**
     * @param Builder<Translator> $query
     * @param bool $value
     * @return Builder<Translator>
     */
    public function scopeAdmin(Builder $query, bool $value = true): Builder
    {
        return $query->where('admin', $value);
    }


    /**
     * @param list<int> $existingLanguageIds
     * @return list<string>
     */
    public static function notifyAdminImportedLanguages(array $existingLanguageIds): array
    {
        /** @var list<string> $newLanguages Names plucked from the language models. */
        $newLanguages = Language::all()
            ->reject(function (Language $language) use ($existingLanguageIds) {
                return in_array($language->id, $existingLanguageIds);
            })->pluck('name')->all();
        Translator::query()->admin()->each(function (Translator $translator) use ($newLanguages) {
            $translator->notify(new FlashMessage($newLanguages ? __('languages::languages.import_languages_success', ['languages' => implode(', ', $newLanguages)]) . __('languages::global.reload_suggestion') : __('languages::languages.import_languages_success_nothing_imported')));
        });
        return $newLanguages;
    }

    /**
     * @param int $total
     * @param Language $language
     * @return int
     */
    public static function notifyAdminImportedTranslations(int $total, Language $language): int
    {
        $total = $language->translations()->count() - $total;
        Translator::query()->admin()->each(function (Translator $translator) use ($total, $language) {
            $translator->notify(new FlashMessage(__('languages::languages.import_translations_success', ['total' => $total, 'language_code' => $language->code]) . __('languages::global.reload_suggestion')));
        });
        return $total;
    }

    /**
     * @param int $total
     * @param Language $language
     * @return int
     */
    public static function notifyAdminImportedMissingTranslations(int $total, Language $language): int
    {
        $total = $language->translations()->count() - $total;
        Translator::query()->admin()->where('admin', true)->each(function (Translator $translator) use ($total, $language) {
            $translator->notify(new FlashMessage(__('languages::languages.find_missing_translations_success', ['total' => $total, 'language_code' => $language->code]) . __('languages::global.reload_suggestion')));
        });
        return $total;
    }

    /**
     * @param int $total
     * @param Language $language
     * @return int
     */
    public static function notifyAdminExportedTranslationsPerLanguage(int $total, Language $language): int
    {
        Translator::query()->admin()->each(function (Translator $translator) use ($total, $language) {
            $translator->notify(new FlashMessage($total ? __('languages::translations.export_language_success', ['language' => $language->name, 'total' => $total]) . __('languages::global.reload_suggestion') : __('languages::translations.nothing_exported')));
        });
        return $total;
    }

    /**
     * @param int $total
     * @param Collection<int, Language> $languages
     * @return int
     */
    public static function notifyAdminExportedTranslationsAllLanguages(int $total, Collection $languages): int
    {
        Translator::query()->admin()->each(function (Translator $translator) use ($total, $languages) {
            /** @var list<string> $languageNames */
            $languageNames = $languages->pluck('name')->all();
            $translator->notify(new FlashMessage($total ? __('languages::translations.export_languages_success', ['languages' => implode(', ', $languageNames), 'total' => $total]) . __('languages::global.reload_suggestion') : __('languages::translations.nothing_exported')));
        });
        return $total;
    }

    /**
     * @param int $total
     * @param Language $language
     * @return int
     */
    public static function notifyAdminApprovedTranslationsPerLanguage(int $total, Language $language): int
    {
        Translator::query()->admin()->each(function (Translator $translator) use ($total, $language) {
            $translator->notify(new FlashMessage($total ? __('languages::translations.approved_language_success', ['language' => $language->name, 'total' => $total]) . __('languages::global.reload_suggestion') : __('languages::translations.nothing_exported')));
        });
        return $total;
    }

    /**
     * @param Translation $translation
     * @return Translation
     */
    public static function notifyAdminUpdatedAllLanguages(Translation $translation): Translation
    {
        Translator::query()->admin()->each(function (Translator $translator) use ($translation) {
            $translator->notify(new FlashMessage(__('languages::translations.updated_all_languages', ['translation_id' => $translation->id]) . ' ' . __('languages::global.reload_suggestion')));
        });
        return $translation;
    }

}
