<?php

namespace LaravelArab\Tarjama\Tests;

use Illuminate\Support\Facades\DB;
use LaravelArab\Tarjama\Collection;
use LaravelArab\Tarjama\Translator;
use LaravelArab\Tarjama\Translatable;
use Illuminate\Database\Eloquent\Model;
use LaravelArab\Tarjama\Facades\Tarjama;
use LaravelArab\Tarjama\Tests\Models\Article;

class TranslationTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Add another language
        config()->set('tarjama.locales', ['en', 'da']);

        // Turn on multilingual
        config()->set('tarjama.enabled', true);
    }

    public function testCheckingModelIsTranslatable()
    {
        $this->assertTrue(Tarjama::translatable(Article::class));
        $this->assertTrue(Tarjama::translatable(Article::class));
    }

    public function testCheckingModelIsNotTranslatable()
    {
        $this->assertFalse(Tarjama::translatable(NotTranslatableModel::class));
        $this->assertFalse(Tarjama::translatable(StillNotTranslatableModel::class));
    }

    public function testGettingModelTranslatableAttributes()
    {
        $this->assertEquals(['name'], (new Article())->getTranslatableAttributes());
        $this->assertEquals([], (new ActuallyTranslatableModel())->getTranslatableAttributes());
    }

    public function testGettingTranslatorCollection()
    {
        $collection = Article::all()->translate('da');

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertInstanceOf(Translator::class, $collection[0]);
    }

    public function testGettingTranslatorModelOfNonExistingTranslation()
    {
        $model = Article::first()->translate('da');

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

        $model = Article::first()->translate('da');

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
        $model = Article::first()->translate('da');

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

        $model = Article::first()->translate('da');

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

        $model = Article::first()->translate('da');

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

        $model = Article::first()->translate('da');

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
        $model = Article::first()->translate('da');

        $this->assertFalse($model->translationAttributeExists('name'));
        $this->assertFalse($model->translationAttributeModified('name'));
    }

    public function testCreatingNewTranslation()
    {
        $model = Article::first()->translate('da');

        $model->createTranslation('name', 'Danish Title Here');

        $model = Article::first()->translate('da');

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

        $model = Article::first()->translate('da');

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

        $model = Article::first()->translate('da');

        $this->assertInstanceOf(Translator::class, $model);
        $this->assertEquals('da', $model->getLocale());
        $this->assertEquals('New Title', $model->name);
        $this->assertTrue($model->getRawAttributes()['name']['exists']);
        $this->assertFalse($model->getRawAttributes()['name']['modified']);
        $this->assertEquals('da', $model->getRawAttributes()['name']['locale']);
    }

    // public function testSearchingTranslations()
    // {
    //     // dd(ActuallyTranslatableModel::whereTranslation('slug', '=', 'name-1', ['fr'])->get());
    //     //Default locale
    //     $this->assertEquals(ActuallyTranslatableModel::whereTranslation('slug', 'name-0')->count(), 1);

    //     //Default locale, but default excluded
    //     $this->assertEquals(ActuallyTranslatableModel::whereTranslation('slug', '=', 'name-0', [], false)->count(), 0);

    //     //Translation, all locales
    //     $this->assertEquals(ActuallyTranslatableModel::whereTranslation('slug', 'name-1')->count(), 1);

    //     //Translation, wrong locale-array
    //     $this->assertEquals(ActuallyTranslatableModel::whereTranslation('slug', '=', 'name-1', ['de'])->count(), 0);

    //     //Translation, correct locale-array
    //     $this->assertEquals(ActuallyTranslatableModel::whereTranslation('slug', '=', 'name-1', ['de', 'pt'])->count(), 1);

    //     //Translation, wrong locale
    //     $this->assertEquals(ActuallyTranslatableModel::whereTranslation('slug', '=', 'name-1', 'de')->count(), 0);

    //     //Translation, correct locale
    //     $this->assertEquals(ActuallyTranslatableModel::whereTranslation('slug', '=', 'name-1', 'pt')->count(), 1);
    // }
}

class NotTranslatableModel extends Model
{
    protected $table = 'articles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'decription',
    ];
}

class StillNotTranslatableModel extends Model
{
    protected $table = 'articles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'decription',
    ];

    protected $translatable = ['name'];
}

class ActuallyTranslatableModel extends Model
{
    protected $table = 'articles';

    use Translatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'description', 'slug',
    ];
}
