<?php

namespace Riomigal\Languages\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin Builder
 */
class Translation extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'language_id', 'language_code', 'relative_path', 'relative_pathname', 'shared_identifier', 'file', 'type', 'key', 'value', 'old_value', 'approved', 'needs_translation', 'updated_translation'
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'approved' => 'boolean',
        'needs_translation' => 'boolean',
        'updated_translation' => 'boolean',
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
}
