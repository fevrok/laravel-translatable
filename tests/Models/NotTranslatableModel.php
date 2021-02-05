<?php

namespace LaravelArab\Tarjama\Tests\Models;

use LaravelArab\Tarjama\Translatable;
use Illuminate\Database\Eloquent\Model;

class NotTranslatableModel extends Model
{
    protected $table = 'articles';

    protected $guarded = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'slug',
    ];
}
