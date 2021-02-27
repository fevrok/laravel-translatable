<?php

namespace Fevrok\Translatable\Tests;

use Fevrok\Translatable\Collection;
use Fevrok\Translatable\Facades\Translatable;
use Fevrok\Translatable\HasTranslations;
use Fevrok\Translatable\Models\Translation;
use Fevrok\Translatable\Tests\Models\NotTranslatableModel;
use Fevrok\Translatable\Tests\Models\TestsTranslationsModel;
use Fevrok\Translatable\Translator;
use Illuminate\Support\Facades\DB;

class TranslationTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Turn on multilingual
        config()->set('translatable.enabled', true);
    }

    /** @test */
    public function checking_model_is_translatable()
    {
        $article = TranslatableModel::first();
        $articles = TranslatableModel::get();

        $traits = class_uses_recursive(get_class($article));

        $this->assertTrue(in_array(HasTranslations::class, $traits));
        $this->assertTrue(property_exists($article, 'translatable'));
        $this->assertTrue($article->translatable());
        $this->assertTrue(Translatable::translatable($article));
        $this->assertTrue(Translatable::translatable($articles));
        $this->assertTrue(Translatable::translatable(TranslatableModel::class));
    }

    /** @test */
    public function checking_model_is_not_translatable()
    {
        $article = NotTranslatableModel::first();
        $articles = NotTranslatableModel::get();

        $traits = class_uses_recursive(get_class($article));

        $this->assertFalse(in_array(HasTranslations::class, $traits));
        $this->assertFalse(property_exists($article, 'translatable'));
        $this->assertFalse(Translatable::translatable($article));
        $this->assertFalse(Translatable::translatable($articles));
        $this->assertFalse(Translatable::translatable(NotTranslatableModel::class));
        $this->assertFalse(Translatable::translatable(StillNotTranslatableModel::class));
    }

    /** @test */
    public function getting_model_translatable_attributes()
    {
        $this->assertEquals(['name'], (new TranslatableModel())->getTranslatableAttributes());
        $this->assertEquals([], (new ActuallyTranslatableModel())->getTranslatableAttributes());
    }

    /** @test */
    public function getting_translator_collection()
    {
        $collection = TranslatableModel::all()->translate('da');

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertInstanceOf(Translator::class, $collection[0]);
    }

    /** @test */
    public function getting_translator_model_of_non_existing_translation()
    {
        $model = TranslatableModel::first()->translate('da');

        $this->assertInstanceOf(Translator::class, $model);
        $this->assertEquals('da', $model->getLocale());
        $this->assertEquals('name 1', $model->name);
        $this->assertFalse($model->getRawAttributes()['name']['exists']);
        $this->assertFalse($model->getRawAttributes()['name']['modified']);
        $this->assertEquals('da', $model->getRawAttributes()['name']['locale']);
        $this->assertEquals('name 1', $model->getOriginalAttribute('name'));
    }

    /** @test */
    public function getting_translator_model_of_existing_translation()
    {
        DB::table('translations')->insert([
            'table_name'  => 'articles',
            'column_name' => 'name',
            'foreign_key' => 1,
            'locale'      => 'da',
            'value'       => 'Foo Bar Post',
        ]);

        $model = TranslatableModel::first()->translate('da');

        $this->assertInstanceOf(Translator::class, $model);
        $this->assertEquals('da', $model->getLocale());
        $this->assertEquals('Foo Bar Post', $model->name);
        $this->assertTrue($model->getRawAttributes()['name']['exists']);
        $this->assertFalse($model->getRawAttributes()['name']['modified']);
        $this->assertEquals('da', $model->getRawAttributes()['name']['locale']);
        $this->assertEquals('name 1', $model->getOriginalAttribute('name'));
    }

    /** @test */
    public function saving_non_existing_translator_model()
    {
        $model = TranslatableModel::first()->translate('da');

        $this->assertInstanceOf(Translator::class, $model);
        $this->assertEquals('da', $model->getLocale());
        $this->assertEquals('name 1', $model->name);
        $this->assertFalse($model->getRawAttributes()['name']['exists']);
        $this->assertFalse($model->getRawAttributes()['name']['modified']);
        $this->assertEquals('da', $model->getRawAttributes()['name']['locale']);
        $this->assertEquals('name 1', $model->getOriginalAttribute('name'));

        $model->name = 'Danish Title';

        $this->assertEquals('Danish Title', $model->name);
        $this->assertFalse($model->getRawAttributes()['name']['exists']);
        $this->assertTrue($model->getRawAttributes()['name']['modified']);
        $this->assertEquals('da', $model->getRawAttributes()['name']['locale']);
        $this->assertEquals('name 1', $model->getOriginalAttribute('name'));

        $model->save();

        $this->assertEquals('Danish Title', $model->name);
        $this->assertTrue($model->getRawAttributes()['name']['exists']);
        $this->assertFalse($model->getRawAttributes()['name']['modified']);
        $this->assertEquals('da', $model->getRawAttributes()['name']['locale']);
        $this->assertEquals('name 1', $model->getOriginalAttribute('name'));

        $model = TranslatableModel::first()->translate('da');

        $this->assertInstanceOf(Translator::class, $model);
        $this->assertEquals('da', $model->getLocale());
        $this->assertEquals('Danish Title', $model->name);
        $this->assertTrue($model->getRawAttributes()['name']['exists']);
        $this->assertFalse($model->getRawAttributes()['name']['modified']);
        $this->assertEquals('da', $model->getRawAttributes()['name']['locale']);
        $this->assertEquals('name 1', $model->getOriginalAttribute('name'));
    }

    /** @test */
    public function saving_existing_translator_model()
    {
        DB::table('translations')->insert([
            'table_name'  => 'articles',
            'column_name' => 'name',
            'foreign_key' => 1,
            'locale'      => 'da',
            'value'       => 'Foo Bar Post',
        ]);

        $model = TranslatableModel::first()->translate('da');

        $this->assertInstanceOf(Translator::class, $model);
        $this->assertEquals('da', $model->getLocale());
        $this->assertEquals('Foo Bar Post', $model->name);
        $this->assertTrue($model->getRawAttributes()['name']['exists']);
        $this->assertFalse($model->getRawAttributes()['name']['modified']);
        $this->assertEquals('da', $model->getRawAttributes()['name']['locale']);
        $this->assertEquals('name 1', $model->getOriginalAttribute('name'));

        $model->name = 'Danish Title';

        $this->assertEquals('Danish Title', $model->name);
        $this->assertTrue($model->getRawAttributes()['name']['exists']);
        $this->assertTrue($model->getRawAttributes()['name']['modified']);
        $this->assertEquals('da', $model->getRawAttributes()['name']['locale']);
        $this->assertEquals('name 1', $model->getOriginalAttribute('name'));

        $model->save();

        $this->assertEquals('Danish Title', $model->name);
        $this->assertTrue($model->getRawAttributes()['name']['exists']);
        $this->assertFalse($model->getRawAttributes()['name']['modified']);
        $this->assertEquals('da', $model->getRawAttributes()['name']['locale']);
        $this->assertEquals('name 1', $model->getOriginalAttribute('name'));

        $model = TranslatableModel::first()->translate('da');

        $this->assertInstanceOf(Translator::class, $model);
        $this->assertEquals('da', $model->getLocale());
        $this->assertEquals('Danish Title', $model->name);
        $this->assertTrue($model->getRawAttributes()['name']['exists']);
        $this->assertFalse($model->getRawAttributes()['name']['modified']);
        $this->assertEquals('da', $model->getRawAttributes()['name']['locale']);
        $this->assertEquals('name 1', $model->getOriginalAttribute('name'));
    }

    /** @test */
    public function getting_translation_meta_data_from_translator()
    {
        $model = TranslatableModel::first()->translate('da');

        $this->assertFalse($model->translationAttributeExists('name'));
        $this->assertFalse($model->translationAttributeModified('name'));
    }

    /** @test */
    public function getting_new_translation()
    {
        $model = TranslatableModel::first()->translate('da');

        $model->createTranslation('name', 'Danish Title Here');

        $model = TranslatableModel::first()->translate('da');

        $this->assertInstanceOf(Translator::class, $model);
        $this->assertEquals('da', $model->getLocale());
        $this->assertEquals('Danish Title Here', $model->name);
        $this->assertTrue($model->getRawAttributes()['name']['exists']);
        $this->assertFalse($model->getRawAttributes()['name']['modified']);
        $this->assertEquals('da', $model->getRawAttributes()['name']['locale']);
    }

    /** @test */
    public function updating_translation()
    {
        DB::table('translations')->insert([
            'table_name'  => 'articles',
            'column_name' => 'name',
            'foreign_key' => 1,
            'locale'      => 'da',
            'value'       => 'Title',
        ]);

        $model = TranslatableModel::first()->translate('da');

        $this->assertInstanceOf(Translator::class, $model);
        $this->assertEquals('da', $model->getLocale());
        $this->assertEquals('Title', $model->name);
        $this->assertTrue($model->getRawAttributes()['name']['exists']);
        $this->assertFalse($model->getRawAttributes()['name']['modified']);
        $this->assertEquals('da', $model->getRawAttributes()['name']['locale']);

        $model->name = 'New Title';

        $this->assertInstanceOf(Translator::class, $model);
        $this->assertEquals('da', $model->getLocale());
        $this->assertEquals('New Title', $model->name);
        $this->assertTrue($model->getRawAttributes()['name']['exists']);
        $this->assertTrue($model->getRawAttributes()['name']['modified']);
        $this->assertEquals('da', $model->getRawAttributes()['name']['locale']);

        $model->save();

        $this->assertInstanceOf(Translator::class, $model);
        $this->assertEquals('da', $model->getLocale());
        $this->assertEquals('New Title', $model->name);
        $this->assertTrue($model->getRawAttributes()['name']['exists']);
        $this->assertFalse($model->getRawAttributes()['name']['modified']);
        $this->assertEquals('da', $model->getRawAttributes()['name']['locale']);

        $model = TranslatableModel::first()->translate('da');

        $this->assertInstanceOf(Translator::class, $model);
        $this->assertEquals('da', $model->getLocale());
        $this->assertEquals('New Title', $model->name);
        $this->assertTrue($model->getRawAttributes()['name']['exists']);
        $this->assertFalse($model->getRawAttributes()['name']['modified']);
        $this->assertEquals('da', $model->getRawAttributes()['name']['locale']);
    }

    /** @test */
    public function searching_translations()
    {
        DB::table('translations')->insert([
            'table_name'  => 'articles',
            'column_name' => 'slug',
            'foreign_key' => 1,
            'locale'      => 'pt',
            'value'       => 'nome-1',
        ]);

        DB::table('translations')->insert([
            'table_name'  => 'articles',
            'column_name' => 'slug',
            'foreign_key' => 2,
            'locale'      => 'pt',
            'value'       => 'nome-2',
        ]);

        //Default locale
        $this->assertEquals(1, ActuallyTranslatableModel::whereTranslation('slug', 'nome-1')->count());

        //Default locale, but default excluded
        $this->assertEquals(0, ActuallyTranslatableModel::whereTranslation('slug', '=', 'nome-1', [], false)->count());

        //Translation, all locales
        $this->assertEquals(1, ActuallyTranslatableModel::whereTranslation('slug', 'nome-2')->count());

        //Translation, wrong locale-array
        $this->assertEquals(0, ActuallyTranslatableModel::whereTranslation('slug', '=', 'nome-2', ['de'])->count());

        //Translation, correct locale-array
        $this->assertEquals(1, ActuallyTranslatableModel::whereTranslation('slug', '=', 'nome-2', ['de', 'pt'])->count());

        //Translation, wrong locale
        $this->assertEquals(0, ActuallyTranslatableModel::whereTranslation('slug', '=', 'nome-2', 'de')->count());

        //Translation, correct locale
        $this->assertEquals(1, ActuallyTranslatableModel::whereTranslation('slug', '=', 'nome-2', 'pt')->count());
    }

    /** @test */
    public function switching_current_locale()
    {
        DB::table('translations')->insert([
            'table_name'  => 'articles',
            'column_name' => 'name',
            'foreign_key' => 1,
            'locale'      => 'pt',
            'value'       => 'nome 1',
        ]);

        $model = TranslatableModel::find(1);

        app()->setLocale('pt');

        $this->assertEquals('nome 1', $model->getTranslatedAttribute('name'));

        app()->setLocale('en');

        $this->assertEquals('name 1', $model->getTranslatedAttribute('name'));
    }

    /** @test */
    public function using_custom_translations_table()
    {
        TestsTranslationsModel::create([
            'table_name'  => 'articles',
            'column_name' => 'slug',
            'foreign_key' => 1,
            'locale'      => 'pt',
            'value'       => 'nome-1',
        ]);

        TestsTranslationsModel::create([
            'table_name'  => 'articles',
            'column_name' => 'slug',
            'foreign_key' => 2,
            'locale'      => 'pt',
            'value'       => 'nome-2',
        ]);

        //Default locale
        $this->assertEquals(1, CustomTranslatableModel::whereTranslation('slug', 'nome-1')->count());

        //Default locale, but default excluded
        $this->assertEquals(0, CustomTranslatableModel::whereTranslation('slug', '=', 'nome-1', [], false)->count());

        //Translation, all locales
        $this->assertEquals(1, CustomTranslatableModel::whereTranslation('slug', 'nome-2')->count());

        //Translation, wrong locale-array
        $this->assertEquals(0, CustomTranslatableModel::whereTranslation('slug', '=', 'nome-2', ['de'])->count());

        //Translation, correct locale-array
        $this->assertEquals(1, CustomTranslatableModel::whereTranslation('slug', '=', 'nome-2', ['de', 'pt'])->count());

        //Translation, wrong locale
        $this->assertEquals(0, CustomTranslatableModel::whereTranslation('slug', '=', 'nome-2', 'de')->count());

        //Translation, correct locale
        $this->assertEquals(1, CustomTranslatableModel::whereTranslation('slug', '=', 'nome-2', 'pt')->count());
    }

    /** @test */
    public function using_the_right_translations_model()
    {
        $normal = ActuallyTranslatableModel::first();

        Translation::create([
            'table_name'  => 'articles',
            'column_name' => 'name',
            'foreign_key' => $normal->id,
            'locale'      => 'pt',
            'value'       => 'nome-1',
        ]);

        $this->assertInstanceOf(Translation::class, $normal->translations()->first());
        $this->assertNotInstanceOf(TestsTranslationsModel::class, $normal->translations()->first());

        $custom = CustomTranslatableModel::first();

        TestsTranslationsModel::create([
            'table_name'  => 'articles',
            'column_name' => 'name',
            'foreign_key' => $custom->id,
            'locale'      => 'pt',
            'value'       => 'nome-2',
        ]);

        $this->assertInstanceOf(Translation::class, $custom->translations()->first());
        $this->assertInstanceOf(TestsTranslationsModel::class, $custom->translations()->first());
    }

    /** @test */
    public function is_translate_method_loads_relationship()
    {
        $model = ActuallyTranslatableModel::first();

        $this->assertFalse($model->relationLoaded('translations'));

        $model->translate('pt');

        $this->assertTrue($model->relationLoaded('translations'));
    }
}

class StillNotTranslatableModel extends NotTranslatableModel
{
    protected $translatable = ['name'];
}

class ActuallyTranslatableModel extends NotTranslatableModel
{
    use HasTranslations;
}

class TranslatableModel extends  NotTranslatableModel
{
    use HasTranslations;

    protected $translatable = ['name'];
}

class CustomTranslatableModel extends  NotTranslatableModel
{
    use HasTranslations;

    protected $translatable = ['name'];

    protected $translations_model = TestsTranslationsModel::class;
}
