<?php

namespace LaravelArab\Tarjama\Models;

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
     * @method public function scopeWhereColumnName($query, $value)
     * 
     * @method public function scopeWhereLocale($query, $value)
     * @method public function scopeOrWhereLocale($query, $value)
     * @method public function scopeWhereInLocale($query, $value)
     * 
     */
}
