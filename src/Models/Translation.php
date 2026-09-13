<?php

namespace Riomigal\Languages\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property int $language_id
 * @property string $language_code
 * @property string $shared_identifier
 * @property bool $is_vendor
 * @property string $type
 * @property string|null $namespace
 * @property string|null $group
 * @property string $key
 * @property string|null $value
 * @property string|null $old_value
 * @property bool $approved
 * @property bool $needs_translation
 * @property bool $updated_translation
 * @property int|null $updated_by
 * @property int|null $previous_updated_by
 * @property int|null $approved_by
 * @property int|null $previous_approved_by
 * @property bool $exported
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property Language $language
 * @property Translator|null $approvedBy
 * @property Translator|null $updatedBy
 * @property string $approver
 * @property string $updater
 *
 * @mixin Builder<Translation>
 */
class Translation extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'language_id', 'language_code', 'shared_identifier', 'is_vendor', 'type', 'namespace',
        'group', 'key', 'value', 'old_value', 'approved', 'needs_translation', 'updated_translation',
        'updated_by', 'previous_updated_by', 'approved_by', 'previous_approved_by', 'exported'
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_vendor' => 'boolean',
        'approved' => 'boolean',
        'needs_translation' => 'boolean',
        'updated_translation' => 'boolean',
        'exported' => 'boolean'
    ];

    /**
     * Invalidate runtime translations after individual creates, updates and deletes.
     */
    protected static function booted(): void
    {
        static::saved(function (Translation $translation): void {
            $translation->getConnection()->afterCommit(static::bumpCacheVersion(...));
        });
        static::deleted(function (Translation $translation): void {
            $translation->getConnection()->afterCommit(static::bumpCacheVersion(...));
        });
    }

    /**
     * Create a new Eloquent model instance.
     *
     * @template TAttribute
     * @param array<string, TAttribute> $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $table = config('languages.table_translations');
        if ($table !== null && !is_string($table)) {
            throw new \TypeError('languages.table_translations must be a string or null.');
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
     * @return BelongsTo<Language, $this>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    /**
     * @return BelongsTo<Translator, $this>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Translator::class, 'approved_by', 'id');
    }

    /**
     * @return BelongsTo<Translator, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(Translator::class, 'updated_by', 'id');
    }

    /**
     * @param Builder<Translation> $query
     * @param bool $value
     * @return Builder<Translation>
     */
    public function scopeIsUpdated(Builder $query, bool $value = true): Builder
    {
        return $query->where('updated_translation', $value);
    }

    /**
     * @param Builder<Translation> $query
     * @param bool $value
     * @return Builder<Translation>
     */
    public function scopeApproved(Builder $query, bool $value = true): Builder
    {
        return $query->where('approved', $value);
    }

    /**
     * @param Builder<Translation> $query
     * @param bool $value
     * @return Builder<Translation>
     */
    public function scopeExported(Builder $query, bool $value = true): Builder
    {
        return $query->where('exported', $value);
    }

    /**
     * @param Builder<Translation> $query
     * @param bool $value
     * @return Builder<Translation>
     */
    public function scopeIsVendor(Builder $query, bool $value = true): Builder
    {
        return $query->where('is_vendor', $value);
    }

    /**
     * @param Builder<Translation> $query
     * @param array<array-key, string>|string $value
     * @return Builder<Translation>
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
     * @param Builder<Translation> $query
     * @param array<array-key, int|string>|string $value
     * @return Builder<Translation>
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
     * @param Builder<Translation> $query
     * @param array<array-key, int|string>|string $value
     * @return Builder<Translation>
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
     * @param Builder<Translation> $query
     * @param bool $value
     * @return Builder<Translation>
     */
    public function scopeNeedsTranslation(Builder $query, bool $value = true): Builder
    {
        return $query->where('needs_translation', $value);
    }

    /**
     * @param string $locale
     * @param string|null $group
     * @param string|null $namespace
     * @return array<array-key, string|null>
     */
    public static function getCachedTranslations(string $locale, string|null $group = null, string|null $namespace = null): array {
        // Never publish uncommitted values to the shared cache, including on rollback.
        if ((new self())->getConnection()->transactionLevel() > 0) {
            return self::loadTranslations($locale, $group, $namespace);
        }

        return Cache::rememberForever(self::translationCacheKey($locale, $group, $namespace), function() use ($locale, $group, $namespace) {
            return self::loadTranslations($locale, $group, $namespace);
        });
    }

    /**
     * @return array<array-key, string|null>
     */
    private static function loadTranslations(string $locale, ?string $group, ?string $namespace): array
    {
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
            })->each(function(Translation $translation) use(&$array) {
                $array[$translation->key] = $translation->approved ? $translation->value : $translation->old_value;
            });
        return $array;
    }

    /**
     * @param string $locale
     * @param string|null $group
     * @param string|null $namespace
     * @return void
     */
    public static function unsetCachedTranslation(string $locale, string|null $group = null, string|null $namespace = null): void
    {
        Cache::forget(self::translationCacheKey($locale, $group, $namespace));
    }

    /**
     * Bulk writes bypass model events; call this after each successful bulk write.
     */
    public static function invalidateCacheAfterWrite(): void
    {
        (new self())->getConnection()->afterCommit(static::bumpCacheVersion(...));
    }

    /**
     * Advance the shared version without depending on targeted cache-key matching.
     */
    public static function bumpCacheVersion(): void
    {
        self::cacheVersion(true);
    }

    /**
     * Build the key once for both cache reads and targeted invalidation.
     */
    private static function translationCacheKey(string $locale, ?string $group, ?string $namespace): string
    {
        return self::cachePrefix() . ':v' . self::cacheVersion()
            . ':' . $locale . ':' . ($group ?? '') . ':' . ($namespace ?? '');
    }

    /**
     * Reads are lock-free once initialized. Serialize initialization and bumps,
     * since file-cache increments and rememberForever initialization are not atomic.
     */
    private static function cacheVersion(bool $increment = false): int
    {
        $cacheKey = self::cachePrefix() . ':version';
        $version = self::readCacheVersion($cacheKey);
        if (!$increment && is_int($version)) {
            return $version;
        }

        $version = Cache::lock($cacheKey . ':lock', 10)->block(5, function () use ($cacheKey, $increment): int {
            $version = self::readCacheVersion($cacheKey);
            if ($increment || !is_int($version)) {
                // A time-based floor avoids reusing old keys if just the version is evicted.
                $version = max(is_int($version) ? $version + 1 : 0, (int) (microtime(true) * 1000000));
                Cache::forever($cacheKey, $version);
            }

            return $version;
        });

        if (!is_int($version)) {
            throw new \TypeError('Translation cache version must be an integer.');
        }

        return $version;
    }

    private static function readCacheVersion(string $cacheKey): ?int
    {
        $version = Cache::get($cacheKey);
        // Redis may return an integer stored without serialization as a string.
        if (is_string($version) && ctype_digit($version)) {
            return (int) $version;
        }

        return is_int($version) ? $version : null;
    }

    private static function cachePrefix(): string
    {
        $cachePrefix = config('languages.cache_key');
        if (!is_string($cachePrefix)) {
            throw new \TypeError('languages.cache_key must be a string.');
        }

        return $cachePrefix;
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
