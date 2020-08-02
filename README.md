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

### Setting custom translation model

If you want to choose another translation model, you will need to:

- If the custom table is not created, you will need to create it. Like that migration suggests:

```php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateItemsTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('items_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('table_name');
            $table->string('column_name');
            $table->integer('foreign_key')->unsigned();
            $table->string('locale');
            $table->text('value');

            $table->unique(['table_name', 'column_name', 'foreign_key', 'locale']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('items_translations');
    }
}
```

- Create the model that will get translations.

ItemsTranslation.php
```php
class ItemsTranslation extends \LaravelArab\Tarjama\Models\Translation
{
    protected $table = 'items_translations';
}
```

- Inform the model class to `$translations_model` property.

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
	
    /**
      * The model used to get translatios.
      *
      * @var string
      */
    protected $translations_model = ItemsTranslation::class;
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
