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

else you have to add the service provider to `config/app.php`

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
    * Set whether or not the translations is enbaled.
    */
   'enabled' => true,

   /**
    * Select default language
    */
   'locale' => 'en',

];
```
And update your config accordingly.

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
        'description',
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

Add `$translations_model` property and  give it to the model you wanna customize,

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

## Usage

### Eager-load translations

```php
// Loads all translations
$posts = Post::with('translations')->get();

// Loads all translations
$posts = Post::all();
$posts->load('translations');

// Loads all translations
$posts = Post::withTranslations()->get();

// Loads specific locales translations
$posts = Post::withTranslations(['en', 'da'])->get();

// Loads specific locale translations
$posts = Post::withTranslation('da')->get();

// Loads current locale translations
$posts = Post::withTranslation('da')->get();
```

### Get default language value

```php
echo $post->title;
```

### Get translated value

```php
echo $post->getTranslatedAttribute('title', 'locale', 'fallbackLocale');
```

If you do not define locale, the current application locale will be used. You can pass in your own locale as a string. If you do not define fallbackLocale, the current application fallback locale will be used. You can pass your own locale as a string. If you want to turn the fallback locale off, pass false. If no values are found for the model for a specific attribute, either for the locale or the fallback, it will set that attribute to null.

### Translate the whole model

```php
$post = $post->translate('locale', 'fallbackLocale');
echo $post->title;
echo $post->body;

// You can also run the `translate` method on the Eloquent collection
// to translate all models in the collection.
$posts = $posts->translate('locale', 'fallbackLocale');
echo $posts[0]->title;
```

If you do not define locale, the current application locale will be used. You can pass in your own locale as a string. If you do not define fallbackLocale, the current application fallback locale will be used. You can pass in your own locale as a string. If you want to turn the fallback locale off, pass false. If no values are found for the model for a specific attribute, either for the locale or the fallback, it will set that attribute to null.

### Check if model is translatable

```php
// with string
if (Translatable::translatable(Post::class)) {
    // it's translatable
}

// with object of Model or Collection
if (Translatable::translatable($post)) {
    // it's translatable
}
```

### Set attribute translations

```php
$post = $post->translate('da');
$post->title = 'foobar';
$post->save();
```

This will update or create the translation for title of the post with the locale da. Please note that if a modified attribute is not translatable, then it will make the changes directly to the model itself. Meaning that it will overwrite the attribute in the language set as default.

### Query translatable Models

To search for a translated value, you can use the `whereTranslation` method.  
For example, to search for the slug of a post, you'd use

```php
$page = Page::whereTranslation('slug', 'my-translated-slug');
// Is the same as
$page = Page::whereTranslation('slug', '=', 'my-translated-slug');
// Search only locale en, de and the default locale
$page = Page::whereTranslation('slug', '=', 'my-translated-slug', ['en', 'de']);
// Search only locale en and de
$page = Page::whereTranslation('slug', '=', 'my-translated-slug', ['en', 'de'], false);
```

`whereTranslation` accepts the following parameter:

* `field` the field you want to search in
* `operator` the operator. Defaults to `=`. Also can be the value \(Same as [where](https://laravel.com/docs/queries#where-clauses)\)
* `value` the value you want to search for
* `locales` the locales you want to search in as an array. Leave as `null` if you want to search all locales
* `default` also search in the default value/locale. Defaults to true.

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
