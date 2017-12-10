# tarjama
It's a Laravel model columns translation manager
## Current working model
![Laravel Tarjama current working model](/images/current_working_model.png)
## Installation

You can install the package via composer:

```bash
composer require laravelarab/tarjama
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
   
   /**
    * Default language
    */
   'locale' => 'en',

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

- Just add the `LaravelArab\Tarjama\Translatable`-trait.

Here's an example of a prepared model:

``` php
use Illuminate\Database\Eloquent\Model;
use LaravelArab\Tarjama\Translatable;

class Item extends Model
{
    use Translatable;
    
    /**
      * The attributes that are Translatable.
      *
      * @var array
      */
    protected $translatable = [
        'name', 'color'
    ];
}
```

### Available methods

Saving translations

``` php
$item = new Item;
$data = array('en' => 'car', 'ar' => 'سيارة');
// column name instead of 'name' and translations array instead of $data
// if 3 parameter is true it will save to translations table automatically
$item->setTranslations('name', $data, true);
// 3 parameter by default is false so you have to save manually
$item->setTranslations('name', $data); // setTranslations($attribute, array $translations, $save = false)
$item->save();
```

Get translations

``` php
$item = new Item::first();
$item->getTranslation('city');  // get current locale translation
$item->getTranslation('city', 'ar'); // getTranslation($attribute, $language = null, $fallback = true)
$item->getTranslationsOf('name', ['ar', 'en']); // getTranslationsOf($attribute, array $languages = null, $fallback = true)
```

Delete translations

``` php
$item = new Item::first();
$item->deleteTranslations(['name', 'color'], 'ar'); // you can also pass array of locales e.g: ['ar', 'en']
```
