# Laravel Translatable

It's a Laravel model columns translation manager

## How it works?

![Laravel Translatable current working model](/images/current_working_model.png)

## Installation

You can install the package via composer:

```bash
composer require fevrok/laravel-translatable
```

If you have Laravel 5.5 and up The package will automatically register itself.

else you have to add the service provider to app/config/app.php

```php
Fevrok\Translatable\TranslatableServiceProvider::class,
```

publish config file and migration.

```bash
php artisan vendor:publish --provider="Fevrok\Translatable\TranslatableServiceProvider"
```

next migrate translations table

```bash
php artisan migrate
```

## Setup

After finishing the installation you can open `config/translatable.php`:

```php
return [

   /**
    * Default Locale || Root columns locale
    * We will use this locale if config('app.locale') translation not exist
    */
   'locale' => 'en',

];
```
Update your config accordingly.

### Making a model translatable

The required steps to make a model translatable are:

- use the `Fevrok\Translatable\Translatable` trait.
- define the model translatable fields in `$translatable` property.

Here's an example of a prepared model:

```php

use Fevrok\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use Translatable;

    /**
      * The attributes that are Translatable.
      *
      * @var array
      */
    protected $translatable = [
        'name',
	'description'
    ];
}
```

### Custom translations model

To get started, publish the assets again this will create new migration update table name to your desire.


CustomTranslation.php
```php
class CustomTranslation extends \Fevrok\Translatable\Models\Translation
{
    protected $table = 'custom_translations';
}
```

Add `$translations_model` property and  give it your custom translations class.

```php
use Illuminate\Database\Eloquent\Model;
use Fevrok\Translatable\Translatable;

class Item extends Model
{
    use Translatable;

    /**
      * The attributes that are Translatable.
      *
      * @var array
      */
    protected $translatable = [
        'name'
    ];
	
    /**
      * The model used to get translatios.
      *
      * @var string
      */
    protected $translations_model = CustomTranslation::class;
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

// This will save if (current locale == default locale OR $save = false)
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

## Maintainers

<table>
  <tbody>
    <tr>
      <td align="center">
        <a href="https://github.com/chadidi">
          <img width="150" height="150" src="https://github.com/chadidi.png?v=3&s=150">
          </br>
          Abdellah Chadidi
        </a>
      </td>
    </tr>
  <tbody>
</table>
