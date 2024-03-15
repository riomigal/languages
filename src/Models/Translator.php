<?php

namespace Riomigal\Languages\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Riomigal\Languages\Notifications\FlashMessage;

/**
 * @mixin Builder
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
     * @var array<int, string>
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
     * @var string[]
     */
    protected $casts = [
        'admin' => 'boolean'
    ];

    /**
     * Create a new Eloquent model instance.
     *
     * @param array $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $this->table = config('languages.table_translators');
        $this->connection = config('languages.db_connection');
        parent::__construct($attributes);
    }

    /**
     * @return BelongsToMany
     */
    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class, config('languages.table_translator_language'));
    }


    /**
     * @param Builder $query
     * @param bool $value
     * @return Builder
     */
    public function scopeAdmin(Builder $query, bool $value = true): Builder
    {
        return $query->where('admin', $value);
    }


    /**
     * @param array $existingLanguageIds
     * @return array
     */
    public static function notifyAdminImportedLanguages(array $existingLanguageIds): array
    {
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
     * @param Collection $languages
     * @return int
     */
    public static function notifyAdminExportedTranslationsAllLanguages(int $total, Collection $languages): int
    {
        Translator::query()->admin()->each(function (Translator $translator) use ($total, $languages) {
            $translator->notify(new FlashMessage($total ? __('languages::translations.export_languages_success', ['languages' => implode(', ', $languages->pluck('name')->all()), 'total' => $total]) . __('languages::global.reload_suggestion') : __('languages::translations.nothing_exported')));
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
}
