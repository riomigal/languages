<?php

namespace Riomigal\Languages\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;

/**
 * @mixin Builder
 */
class Setting extends Authenticatable
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
        'db_loader', 'import_vendor'
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'db_loader' => 'boolean',
        'import_vendor' => 'boolean',
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
}
