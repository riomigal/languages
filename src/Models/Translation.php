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
        'language_id', 'language_code', 'shared_identifier', 'is_vendor', 'type', 'namespace',
        'group', 'key', 'value', 'old_value', 'approved', 'needs_translation', 'updated_translation',
        'updated_by', 'previous_updated_by', 'approved_by', 'previous_approved_by', 'exported'
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'is_vendor' => 'boolean',
        'approved' => 'boolean',
        'needs_translation' => 'boolean',
        'updated_translation' => 'boolean',
        'exported' => 'boolean'
    ];

    /**
     * Create a new Eloquent model instance.
     *
     * @param array $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $this->table = config('languages.table_translations');
        $this->connection = config('languages.db_connection');
        parent::__construct($attributes);
    }

    /**
     * @return BelongsTo
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    /**
     * @return BelongsTo
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Translator::class, 'approved_by', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(Translator::class, 'updated_by', 'id');
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
    public function scopeExported(Builder $query, bool $value = true): Builder
    {
        return $query->where('exported', $value);
    }

    /**
     * @param Builder $query
     * @param bool $value
     * @return Builder
     */
    public function scopeIsVendor(Builder $query, bool $value = true): Builder
    {
        return $query->where('is_vendor', $value);
    }

    /**
     * @param Builder $query
     * @param array|string $value
     * @return Builder
     */
    public function scopeType(Builder $query, array|string $value): Builder
    {
        if(is_array($value)) {
            return $query->whereIn('type', $value);
        } else {
            return $query->where('type', $value);
        }
    }

    /**
     * @param Builder $query
     * @param array|string $value
     * @return Builder
     */
    public function scopeUpdatedBy(Builder $query, array|string $value): Builder
    {
        if(is_array($value)) {
            return $query->whereIn('updated_by', $value);
        } else {
            return $query->where('updated_by', $value);
        }
    }


    /**
     * @param Builder $query
     * @param array|string $value
     * @return Builder
     */
    public function scopeApprovedBy(Builder $query, array|string $value): Builder
    {
        if(is_array($value)) {
            return $query->whereIn('approved_by', $value);
        } else {
            return $query->where('approved_by', $value);
        }
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
     * @param string|null $group
     * @param string|null $namespace
     * @return array
     */
    public static function getCachedTranslations(string $locale, string|null $group = null, string|null $namespace = null): array {

        return Cache::rememberForever(config('languages.cache_key') . $locale . $group ?? '' . $namespace ?? '', function() use ($locale, $group, $namespace) {
        $array = [];
        Translation::select(
            'language_code',
            'namespace',
            'group',
            'key',
            'value',
            'old_value',
            'type',
            'approved'
        )
            ->where('language_code', $locale)
            ->when($group != '*', function($query) use ($group) {
                $query->where('group', $group);
            })
            ->when($namespace != '*', function($query) use ($namespace) {
                $query->where('namespace', $namespace);
            })->each(function(Translation $translation) use(&$array,$locale, $namespace, $group) {
                $array[$translation->key] = $translation->approved ? $translation->value : $translation->old_value;
            });
        return $array;
        });
    }

    /**
     * @param string $locale
     * @param string|null $group
     * @param string|null $namespace
     * @return void
     */
    public static function unsetCachedTranslation(string $locale, string|null $group = null, string|null $namespace = null): void
    {
        Cache::forget(config('languages.cache_key') . $locale . $group ?? '' . $namespace ?? '');
    }

    /**
     * @return string
     */
    public function getApproverAttribute(): string
    {
        return $this->approvedBy ? $this->approvedBy->first_name . ' ' . $this->approvedBy->last_name : '';
    }

    /**
     * @return string
     */
    public function getUpdaterAttribute(): string
    {
        return $this->updatedBy ? $this->updatedBy->first_name . ' ' . $this->updatedBy->last_name : '';
    }
}
