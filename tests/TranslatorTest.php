<?php

namespace Fevrok\Translatable\Tests;

use Fevrok\Translatable\Collection;
use Fevrok\Translatable\Translator;
use Illuminate\Support\Facades\DB;

class TranslatorTest extends TestCase
{
    /** @test */
    public function getting_translator_collection()
    {
        $collection = $this->getModelTranslatorCollection(TranslatableModel::class, 'da');

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertInstanceOf(Translator::class, $collection[0]);
    }

    /** @test */
    public function getting_translator_model_of_non_existing_translation()
    {
        $model = $this->getModelTranslator(TranslatableModel::class, 'da');

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

        $model = $this->getModelTranslator(TranslatableModel::class, 'da');

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
        $model = $this->getModelTranslator(TranslatableModel::class, 'da');

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

        $model = $this->getModelTranslator(TranslatableModel::class, 'da');

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

        $model = $this->getModelTranslator(TranslatableModel::class, 'da');

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

        $model = $this->getModelTranslator(TranslatableModel::class, 'da');

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
        $model = $this->getModelTranslator(TranslatableModel::class, 'da');

        $this->assertFalse($model->translationAttributeExists('name'));
        $this->assertFalse($model->translationAttributeModified('name'));
    }

    /** @test */
    public function getting_new_translation()
    {
        $model = $this->getModelTranslator(TranslatableModel::class, 'da');

        $model->createTranslation('name', 'Danish Title Here');

        $model = $this->getModelTranslator(TranslatableModel::class, 'da');

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

        $model = $this->getModelTranslator(TranslatableModel::class, 'da');

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

        $model = $this->getModelTranslator(TranslatableModel::class, 'da');

        $this->assertInstanceOf(Translator::class, $model);
        $this->assertEquals('da', $model->getLocale());
        $this->assertEquals('New Title', $model->name);
        $this->assertTrue($model->getRawAttributes()['name']['exists']);
        $this->assertFalse($model->getRawAttributes()['name']['modified']);
        $this->assertEquals('da', $model->getRawAttributes()['name']['locale']);
    }
}
