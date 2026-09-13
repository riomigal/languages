<?php

namespace Riomigal\Languages\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property bool $db_loader
 * @property bool $import_vendor
 * @property bool $enable_pending_notifications
 * @property bool $enable_automatic_pending_notifications
 * @property bool $enable_open_ai_translations
 * @property bool $process_running
 * @property bool $enable_multi_host
 * @property string|null $domains
 * @property bool $import_only_from_root_language
 * @property bool $allow_deleting_languages
 *
 * @mixin Builder<Setting>
 */
class Setting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'db_loader',
        'import_vendor',
        'enable_pending_notifications',
        'enable_automatic_pending_notifications',
        'enable_open_ai_translations',
        'process_running',
        'enable_multi_host',
        'domains',
        'import_only_from_root_language',
        'allow_deleting_languages',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'db_loader' => 'boolean',
        'import_vendor' => 'boolean',
        'enable_pending_notifications' => 'boolean',
        'enable_automatic_pending_notifications' => 'boolean',
        'enable_open_ai_translations' => 'boolean',
        'process_running' => 'boolean',
        'enable_multi_host' => 'boolean',
        'import_only_from_root_language' => 'boolean',
        'allow_deleting_languages' => 'boolean',
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
        $table = config('languages.table_settings');
        if ($table !== null && !is_string($table)) {
            throw new \TypeError('languages.table_settings must be a string or null.');
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
     * Returns cached settings
     *
     * @return Setting
     */
    public static function getCached(): Setting
    {
        $cachePrefix = config('languages.cache_key');
        if (!is_string($cachePrefix)) {
            throw new \TypeError('languages.cache_key must be a string.');
        }
        $setting = Cache::rememberForever($cachePrefix . '_settings', function() {
            return Setting::first();
        });
        // The declared return type already rejects an absent settings row.
        return $setting ?? throw new \TypeError('Cached settings must be a Setting, null given.');
    }

    /**
     * Returns cached settings
     *
     * @return Setting
     */
    public static function getFreshCached(): Setting
    {
        $cachePrefix = config('languages.cache_key');
        if (!is_string($cachePrefix)) {
            throw new \TypeError('languages.cache_key must be a string.');
        }
        Cache::forget($cachePrefix . '_settings');
        Cache::forget($cachePrefix . '_has_db_loader_on');
        return self::getCached();
    }

    public static function setJobsRunning(bool $value = true): Setting
    {
        $setting = Setting::first();
        if ($setting === null) {
            throw new \Error('Attempt to assign property "process_running" on null');
        }
        $setting->process_running = $value;
        $setting->save();
        return $setting;
    }

    public static function multiHostEnabled(): bool
    {
        return self::getCached()->enable_multi_host;
    }

    public static function getDomains(): ?string
    {
        $domains = Setting::getCached()->domains ?? config('languages.multiple_db_hosts');
        if ($domains !== null && !is_string($domains)) {
            throw new \TypeError('languages.multiple_db_hosts must be a string or null.');
        }
        return $domains;
    }
}
