# tarjama
It's a Laravel model columns translation manager
## Current working model
![Laravel Tarjama current working model](/images/current_working_model.png)
## Installation

You can install the package via composer:

```bash
composer require spatie/laravel-translatable
```

If you have Laravel 5.5 and up The package will automatically register itself.

else you have to add the service provider to app/config/app.php

```php
LaravelArab\Tarjama\TarjamaServiceProvider::class,
```

If you want to change the default locale, you must publish the config file:
```bash
php artisan vendor:publish --provider="LaravelArab\Tarjama\TarjamaServiceProvider"
```

This is the contents of the published file:
```php
return [
    /*
    |--------------------------------------------------------------------------
    | Tarjama config
    |--------------------------------------------------------------------------
    |
    | Here you can specify Tarjama configs
    |
    */
   
   /**
    * Default language
    */
   'default' => 'en',

   /**
    * Supported Locales e.g: ['en', 'fr', 'ar']
    */
   'locales' => [
   		'ar',
   		'en',
   		'fr'
   	]

];
```
next migrate translations table
```bash
php artisan migrate
```

## Making a model translatable

The required steps to make a model translatable are:

- Just add the `LaravelArab\Tarjama\Transable`-trait.

Here's an example of a prepared model:

``` php
use Illuminate\Database\Eloquent\Model;
use LaravelArab\Tarjama\Transable;

class Item extends Model
{
    use Transable;
}
```

### Available methods

saving translations

``` php
$item = new Item;
$data = array('en' => 'car', 'ar' => 'سيارة');
// column name instead of 'name' and translations array instead of $data
// if 3 parameter is true it will save automatically
$item->setTranslations('name', $data, true);
// 3 parameter by default is false so you have to save manually
$item->setTranslations('name', $data); // setTranslations($attribute, array $translations, $save = false)
$item->save();
```

getting translations

``` php
$item = new Item::first();
$item->getTrans('city', 'ar'); // getTrans($attribute, $language = null, $fallback = true)
$item->getTranslationsOf('name', ['ar', 'en']); // getTranslationsOf($attribute, array $languages = null, $fallback = true)
```

delete translations

``` php
$item = new Item::first();
$item->deleteTranslations(['name', 'color'], 'ar'); // you can also pass array of locales e.g: ['ar', 'en']
$item->deleteTrans('name', 'en'); // you can also pass array of locales e.g: ['ar', 'en']
```