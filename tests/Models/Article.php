<?php

namespace LaravelArab\Tarjama\Tests\Models;

use LaravelArab\Tarjama\Translatable;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use Translatable;

    protected $table = 'articles';

    protected $guarded = [];

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }
}
