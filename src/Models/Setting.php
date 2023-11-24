<?php

namespace Riomigal\Languages\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * @mixin Builder
 */
class Setting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'db_loader',
        'import_vendor',
        'enable_pending_notifications',
        'enable_automatic_pending_notifications',
        'enable_open_ai_translations',
        'process_running'
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'db_loader' => 'boolean',
        'import_vendor' => 'boolean',
        'enable_pending_notifications' => 'boolean',
        'enable_automatic_pending_notifications' => 'boolean',
        'enable_open_ai_translations' => 'boolean',
        'process_running' => 'boolean',
    ];

    /**
     * Create a new Eloquent model instance.
     *
     * @param array $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $this->table = config('languages.table_settings');
        $this->connection = config('languages.db_connection');
        parent::__construct($attributes);
    }

    /**
     * Returns cached settings
     *
     * @return Setting
     */
    public static function getCached(): Setting
    {
        return Cache::rememberForever(config('languages.cache_key') . '_settings', function() {
            return Setting::first();
        });
    }

    /**
     * Returns cached settings
     *
     * @return Setting
     */
    public static function getFreshCached(): Setting
    {
        Cache::forget(config('languages.cache_key') . '_settings');
        return self::getCached();
    }

    public static function setJobsRunning(bool $value = true): Setting
    {
        $setting = Setting::first();
        $setting->process_running = $value;
        $setting->save();
        return $setting;
    }
}
