<?php

namespace Fevrok\Translatable\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'translations';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'table_name',
        'column_name',
        'foreign_key',
        'locale',
        'value',
    ];

    /**
     * You can create this methods and override functionality.
     *
     * @method public function scopeWhereTableName($query, $value)
     *
     * @method public function scopeWhereColumnName($query, $value)
     * @method public function scopeWhereInColumnName($query, $value)
     *
     * @method public function scopeWhereForeignKey($query, $value)
     *
     * @method public function scopeWhereLocale($query, $value)
     * @method public function scopeOrWhereLocale($query, $value)
     * @method public function scopeWhereInLocale($query, $value)
     *
     * @method public function scopeWhereValue($query, $value)
     */

    /**
     * Add operator to value field scope.
     *
     * @param Builder $query
     * @param string $operator
     * @param mixed|null $value
     * @return Builder
     */
    public function scopeWhereValue($query, $operator, $value = null)
    {
        if (is_null($value)) {
            $value = $operator;
            $operator = '=';
        }

        return $query->where('value', $operator, $value);
    }

    /**
     * Looks like whereInLocale not working as expected so added this.
     *
     * @param Builder $query
     * @param mixed $value
     * @return Builder
     */
    public function scopeWhereInLocale($query, $value)
    {
        return $query->whereIn('locale', $value);
    }
}
