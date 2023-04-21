<?php

namespace Riomigal\Languages\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

/**
 * @mixin Builder
 */
class Translation extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'language_id', 'language_code', 'shared_identifier', 'is_vendor', 'type', 'namespace', 'group', 'key', 'value', 'old_value', 'approved', 'needs_translation', 'updated_translation', 'updated_by', 'approved_by'
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'is_vendor' => 'boolean',
        'approved' => 'boolean',
        'needs_translation' => 'boolean',
        'updated_translation' => 'boolean'
    ];

    /**
     * Create a new Eloquent model instance.
     *
     * @param array $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('languages.table_translations');
    }

    /**
     * @return BelongsTo
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    /**
     * @param Builder $query
     * @param bool $value
     * @return Builder
     */
    public function scopeIsUpdated(Builder $query, bool $value = true): Builder
    {
        return $query->where('updated_translation', $value);
    }

    /**
     * @param Builder $query
     * @param bool $value
     * @return Builder
     */
    public function scopeApproved(Builder $query, bool $value = true): Builder
    {
        return $query->where('approved', $value);
    }

    /**
     * @param Builder $query
     * @param bool $value
     * @return Builder
     */
    public function scopeNeedsTranslation(Builder $query, bool $value = true): Builder
    {
        return $query->where('needs_translation', $value);
    }

    /**
     * @param string $locale
     * @param string $group
     * @param string|null $namespace
     * @return array
     */
    public static function getCachedTranslations(string $locale, string $group, string|null $namespace): array {

        return Cache::rememberForever(config('languages.cache_key') . $locale . $group . $namespace, function() use ($locale, $group, $namespace) {
            $array = [];
            Translation::select(
                'language_code',
                'namespace',
                'group',
                'key',
                'value',
                'type',
                'approved'
            )
                ->where('language_code', $locale)
                ->when($group != '*', function($query) use ($group) {
                    $query->where('group', $group);
                })
                ->when($namespace != '*', function($query) use ($namespace) {
                    $query->where('namespace', $namespace);
                })->each(function(Translation $translation) use(&$array) {
                    $array[$translation->key] = $translation->approved ? $translation->value : $translation->old_value;
                });
            return $array;
        });
    }

    /**
     * @param string $locale
     * @param string $group
     * @param string|null $namespace
     * @return void
     */
    public static function unsetCachedTranslation(string $locale, string $group, string|null $namespace): void
    {
        Cache::forget(config('languages.cache_key') . $locale . $group . $namespace);
    }
}
