<?php

namespace LaravelArab\Tarjama\Tests\Models;

use LaravelArab\Tarjama\Translatable;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use Translatable;

    protected $table = 'categories';

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

    public function articles()
    {
        return $this->hasMany(Article::class);
    }
}
