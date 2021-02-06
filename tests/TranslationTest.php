<?php

namespace Fevrok\Translatable\Tests;

use Fevrok\Translatable\Collection;
use Fevrok\Translatable\Facades\Translatable;
use Fevrok\Translatable\HasTranslations;
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

    public function testCheckingModelIsTranslatable()
    {
        $this->assertTrue(Translatable::translatable(TranslatableModel::class));
    }

    public function testCheckingModelIsNotTranslatable()
    {
        $this->assertFalse(Translatable::translatable(NotTranslatableModel::class));
        $this->assertFalse(Translatable::translatable(StillNotTranslatableModel::class));
    }

    public function testGettingModelTranslatableAttributes()
    {
        $this->assertEquals(['name'], (new TranslatableModel())->getTranslatableAttributes());
        $this->assertEquals([], (new ActuallyTranslatableModel())->getTranslatableAttributes());
    }

    public function testGettingTranslatorCollection()
    {
        $collection = TranslatableModel::all()->translate('da');

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertInstanceOf(Translator::class, $collection[0]);
    }

    public function testGettingTranslatorModelOfNonExistingTranslation()
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

    public function testGettingTranslatorModelOfExistingTranslation()
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

    public function testSavingNonExistingTranslatorModel()
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

    public function testSavingExistingTranslatorModel()
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

    public function testGettingTranslationMetaDataFromTranslator()
    {
        $model = TranslatableModel::first()->translate('da');

        $this->assertFalse($model->translationAttributeExists('name'));
        $this->assertFalse($model->translationAttributeModified('name'));
    }

    public function testCreatingNewTranslation()
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

    public function testUpdatingTranslation()
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

    public function testSearchingTranslations()
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

    public function testSwitchingCurrentLocale()
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

    public function testUsingCustomTranslationsTable()
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
