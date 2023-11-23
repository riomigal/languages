<?php

namespace Riomigal\Languages\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
}
