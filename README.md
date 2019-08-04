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
    * Default Locale || Root columns locale
    * We will use this locale if config('app.locale') translation not exist
    */
   'locale' => 'en',

   /**
    * Supported Locales e.g: ['en', 'fr', 'ar']
    */
   'locales' => ['ar', 'en', 'fr']

];
```

next migrate translations table

```bash
php artisan migrate
```

## Making a model translatable

The required steps to make a model translatable are:

- Just use the `LaravelArab\Tarjama\Translatable` trait.

Here's an example of a prepared model:

```php
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

```php
$item = new Item;
$data = array('en' => 'car', 'ar' => 'سيارة');

$item->setTranslations('name', $data); // setTranslations($attribute, array $translations, $save = false)

// or save one translation
$item->setTranslation('name', 'en', 'car', true); // setTranslation($attribute, $locale, $value, $save = false)

// or just do
$item->name = 'car'; // note: this will save automaticaly unless it's the default locale

// save if current locale == default locale OR $save = false
$item->save();
```

Get translations

```php
$item = new Item::first();
// get current locale translation
$item->city
OR
$item->getTranslation('city');

// pass translation locales
$item->getTranslation('city', 'ar'); // getTranslation($attribute, $language = null, $fallback = true)
$item->getTranslationsOf('name', ['ar', 'en']); // getTranslationsOf($attribute, array $languages = null, $fallback = true)
```

Delete translations

```php
$item = new Item::first();
$item->deleteTranslations(['name', 'color'], ['ar', 'en']); // deleteTranslations(array $attributes, $locales = null)
```
