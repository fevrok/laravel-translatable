<?php

namespace LaravelArab\Tarjama\Tests\Models;

use LaravelArab\Tarjama\Translatable;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use Translatable;

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

    protected $translatable = ['name'];

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }
}
