<?php

namespace LaravelArab\Tarjama\Tests\Models;

use LaravelArab\Tarjama\Translatable;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use Translatable;

    protected $table = 'categories';

    protected $guarded = [];

    public function articles()
    {
        return $this->hasMany(Article::class);
    }
}
