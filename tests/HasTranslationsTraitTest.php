<?php

namespace Fevrok\Translatable\Tests;

use Fevrok\Translatable\Facades\Translatable;
use Fevrok\Translatable\HasTranslations;
use Fevrok\Translatable\Models\Translation;
use Fevrok\Translatable\Tests\Models\NotTranslatableModel;
use Fevrok\Translatable\Tests\Models\TestsTranslationsModel;
use Illuminate\Support\Facades\DB;

class HasTranslationsTraitTest extends TestCase
{
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

    // public function scopeWithTranslations(Builder $query, $locales = null, $fallback = true)

    /** @test */
    public function get_model_with_translations()
    {
        $model = ActuallyTranslatableModel::withTranslations('pt')->first();

        $this->assertTrue($model->relationLoaded('translations'));

        $model = ActuallyTranslatableModel::withTranslations(['pt', 'ar'])->first();

        $this->assertTrue($model->relationLoaded('translations'));
    }

    // public function getTranslationsOf($attribute, array $locales = null, $fallback = true)

    /** @test */
    public function is_translation_of_correct()
    {
        $model = TranslatableModel::first();

        $this->assertEquals(['en' => 'name 1'], $model->getTranslationsOf('name'));

        $this->assertEquals(['pt' => 'name 1'], $model->getTranslationsOf('name', ['pt']));
        $this->assertEquals(['en' => 'name 1', 'pt' => 'name 1'], $model->getTranslationsOf('name', ['en', 'pt']));
        $this->assertEquals(['pt' => 'name 1', 'en' => 'name 1'], $model->getTranslationsOf('name', ['pt', 'en']));

        $this->assertEquals(['en' => 'name 1'], $model->getTranslationsOf('name', null, false));
        $this->assertEquals(['en' => 'name 1'], $model->getTranslationsOf('name', ['en'], false));
        $this->assertEquals(['pt' => null], $model->getTranslationsOf('name', ['pt'], false));
        $this->assertEquals(['en' => 'name 1', 'pt' => null], $model->getTranslationsOf('name', ['en', 'pt'], false));

        // dd($model->getTranslationsOf('wrong', ['en', 'pt'], false));
    }

    // public function getTranslatedAttribute($attribute, $locale = null, $fallback = true)

    /** @test */
    public function is_translated_attribute_correct_without_translation()
    {
        $model = TranslatableModel::first();

        $this->assertEquals('name 1', $model->getTranslatedAttribute('name'));
        $this->assertEquals('name 1', $model->getTranslatedAttribute('name', 'pt'));
        $this->assertEquals('name 1', $model->getTranslatedAttribute('name', 'en', false));
        $this->assertNull($model->getTranslatedAttribute('name', 'pt', false));
        $this->assertEquals('name 1', $model->getTranslatedAttribute('name', 'en', true));
        $this->assertEquals('name 1', $model->getTranslatedAttribute('name', 'pt', true));
        $this->assertNull($model->getTranslatedAttribute('name', 'pt', 'ar'));
        $this->assertEquals('name 1', $model->getTranslatedAttribute('name', 'ar', 'en'));
        $this->assertNull($model->getTranslatedAttribute('name', 'ar', 'pt'));

        // wrong column with no translations
        $this->assertNull($model->getTranslatedAttribute('wrong'));
        $this->assertNull($model->getTranslatedAttribute('wrong', 'pt'));
        $this->assertNull($model->getTranslatedAttribute('wrong', 'ar', 'pt'));
        $this->assertNull($model->getTranslatedAttribute('wrong', 'pt', false));
    }

    /** @test */
    public function is_translated_attribute_correct_with_translation()
    {
        $model = TranslatableModel::first();

        Translation::create([
            'table_name'  => 'articles',
            'column_name' => 'name',
            'foreign_key' => $model->id,
            'locale'      => 'pt',
            'value'       => 'nome 1',
        ]);

        $this->assertEquals('nome 1', $model->getTranslatedAttribute('name', 'pt'));
        $this->assertEquals('nome 1', $model->getTranslatedAttribute('name', 'pt', false));
        $this->assertEquals('nome 1', $model->getTranslatedAttribute('name', 'pt', true));
        $this->assertEquals('nome 1', $model->getTranslatedAttribute('name', 'pt', 'ar'));
        $this->assertEquals('name 1', $model->getTranslatedAttribute('name', 'ar', 'en'));
        $this->assertEquals('nome 1', $model->getTranslatedAttribute('name', 'ar', 'pt'));
    }

    /** @test */
    public function is_translated_attribute_correct_with_wrong_translation()
    {
        $model = TranslatableModel::first();

        Translation::create([
            'table_name'  => 'articles',
            'column_name' => 'wrong',
            'foreign_key' => $model->id,
            'locale'      => 'pt',
            'value'       => 'nome 1',
        ]);

        $this->assertNull($model->getTranslatedAttribute('wrong'));
        $this->assertNull($model->getTranslatedAttribute('wrong', 'pt'));
        $this->assertNull($model->getTranslatedAttribute('wrong', 'ar', 'pt'));
        $this->assertNull($model->getTranslatedAttribute('wrong', 'pt', false));
    }

    /** @test */
    public function is_not_translated_attribute_when_translation_off()
    {
        config(['translatable.enabled' => false]);

        $model = TranslatableModel::first();

        $this->assertEquals('name 1', $model->getTranslatedAttribute('name'));
        $this->assertEquals('name 1', $model->getTranslatedAttribute('name', 'pt'));
        $this->assertEquals('name 1', $model->getTranslatedAttribute('name', 'en', false));
        $this->assertEquals('name 1', $model->getTranslatedAttribute('name', 'pt', false));
        $this->assertEquals('name 1', $model->getTranslatedAttribute('name', 'en', true));
        $this->assertEquals('name 1', $model->getTranslatedAttribute('name', 'pt', true));
        $this->assertEquals('name 1', $model->getTranslatedAttribute('name', 'pt', 'ar'));
        $this->assertEquals('name 1', $model->getTranslatedAttribute('name', 'ar', 'en'));
        $this->assertEquals('name 1', $model->getTranslatedAttribute('name', 'ar', 'pt'));

        // wrong column with no translations
        $this->assertNull($model->getTranslatedAttribute('wrong'));
        $this->assertNull($model->getTranslatedAttribute('wrong', 'pt'));
        $this->assertNull($model->getTranslatedAttribute('wrong', 'ar', 'pt'));
        $this->assertNull($model->getTranslatedAttribute('wrong', 'pt', false));

        // refresh the $model and create translation
        $model = $model->fresh();
        Translation::create([
            'table_name'  => 'articles',
            'column_name' => 'name',
            'foreign_key' => $model->id,
            'locale'      => 'pt',
            'value'       => 'nome 1',
        ]);

        $this->assertEquals('name 1', $model->getTranslatedAttribute('name', 'pt'));
        $this->assertEquals('name 1', $model->getTranslatedAttribute('name', 'pt', false));
        $this->assertEquals('name 1', $model->getTranslatedAttribute('name', 'pt', true));
        $this->assertEquals('name 1', $model->getTranslatedAttribute('name', 'pt', 'ar'));
        $this->assertEquals('name 1', $model->getTranslatedAttribute('name', 'ar', 'en'));
        $this->assertEquals('name 1', $model->getTranslatedAttribute('name', 'ar', 'pt'));

        // wrong column with translation
        $model = $model->fresh();
        Translation::create([
            'table_name'  => 'articles',
            'column_name' => 'wrong',
            'foreign_key' => $model->id,
            'locale'      => 'pt',
            'value'       => 'nome 1',
        ]);

        $this->assertNull($model->getTranslatedAttribute('wrong'));
        $this->assertNull($model->getTranslatedAttribute('wrong', 'pt'));
        $this->assertNull($model->getTranslatedAttribute('wrong', 'ar', 'pt'));
        $this->assertNull($model->getTranslatedAttribute('wrong', 'pt', false));
    }


    // public function getTranslatedAttributeMeta($attribute, $locale = null, $fallback = true)

    /** @test */
    public function is_translated_attribute_meta_correct()
    {
        $model = TranslatableModel::first();

        $this->assertEquals(['name 1', 'en', true], $model->getTranslatedAttributeMeta('name'));
        $this->assertEquals(['name 1', 'pt', false], $model->getTranslatedAttributeMeta('name', 'pt'));
        $this->assertEquals(['name 1', 'en', true], $model->getTranslatedAttributeMeta('name', 'en', false));
        $this->assertEquals([null, 'pt', false], $model->getTranslatedAttributeMeta('name', 'pt', false));
        $this->assertEquals(['name 1', 'en', true], $model->getTranslatedAttributeMeta('name', 'en', true));
        $this->assertEquals(['name 1', 'pt', false], $model->getTranslatedAttributeMeta('name', 'pt', true));
        $this->assertEquals([null, 'pt', false], $model->getTranslatedAttributeMeta('name', 'pt', 'ar'));
        $this->assertEquals(['name 1', 'ar', false], $model->getTranslatedAttributeMeta('name', 'ar', 'en'));
        $this->assertEquals([null, 'ar', false], $model->getTranslatedAttributeMeta('name', 'ar', 'pt'));

        // wrong column with no translations
        $this->assertEquals([null, 'en', false], $model->getTranslatedAttributeMeta('wrong'));
        $this->assertEquals([null, 'en', false], $model->getTranslatedAttributeMeta('wrong', 'pt'));
        $this->assertEquals([null, 'en', false], $model->getTranslatedAttributeMeta('wrong', 'ar', 'pt'));
        $this->assertEquals([null, 'en', false], $model->getTranslatedAttributeMeta('wrong', 'pt', false));

        // refresh the $model and create translation
        $model = $model->fresh();
        Translation::create([
            'table_name'  => 'articles',
            'column_name' => 'name',
            'foreign_key' => $model->id,
            'locale'      => 'pt',
            'value'       => 'nome 1',
        ]);

        $this->assertEquals(['nome 1', 'pt', true], $model->getTranslatedAttributeMeta('name', 'pt'));
        $this->assertEquals(['nome 1', 'pt', true], $model->getTranslatedAttributeMeta('name', 'pt', false));
        $this->assertEquals(['nome 1', 'pt', true], $model->getTranslatedAttributeMeta('name', 'pt', true));
        $this->assertEquals(['nome 1', 'pt', true], $model->getTranslatedAttributeMeta('name', 'pt', 'ar'));
        $this->assertEquals(['name 1', 'ar', false], $model->getTranslatedAttributeMeta('name', 'ar', 'en'));
        $this->assertEquals(['nome 1', 'ar', true], $model->getTranslatedAttributeMeta('name', 'ar', 'pt'));

        // wrong column with translation
        $model = $model->fresh();
        Translation::create([
            'table_name'  => 'articles',
            'column_name' => 'wrong',
            'foreign_key' => $model->id,
            'locale'      => 'pt',
            'value'       => 'nome 1',
        ]);

        $this->assertEquals([null, 'en', false], $model->getTranslatedAttributeMeta('wrong'));
        $this->assertEquals([null, 'en', false], $model->getTranslatedAttributeMeta('wrong', 'pt'));
        $this->assertEquals([null, 'en', false], $model->getTranslatedAttributeMeta('wrong', 'ar', 'pt'));
        $this->assertEquals([null, 'en', false], $model->getTranslatedAttributeMeta('wrong', 'pt', false));
    }

    // public function getTranslatableAttributes()

    /** @test */
    public function is_translatable_attributes_correct()
    {
        $model = TranslatableModel::first();

        $this->assertIsArray($model->getTranslatableAttributes());
        $this->assertNotEmpty($model->getTranslatableAttributes());
        $this->assertEquals(['name'], $model->getTranslatableAttributes());

        $model = CustomTranslatableModel::first();

        $this->assertIsArray($model->getTranslatableAttributes());
        $this->assertNotEmpty($model->getTranslatableAttributes());
        $this->assertEquals(['name'], $model->getTranslatableAttributes());

        $model = ActuallyTranslatableModel::first();
        $this->assertEmpty($model->getTranslatableAttributes());
    }

    // public function translationsModel(): Translation

    /** @test */
    public function is_correct_translations_model()
    {
        $model = ActuallyTranslatableModel::first();
        $this->assertInstanceOf(Translation::class, $model->translationsModel());
        $this->assertEquals('translations', $model->translationsModel()->getTable());

        $model = CustomTranslatableModel::first();
        $this->assertInstanceOf(TestsTranslationsModel::class, $model->translationsModel());
        $this->assertEquals('tests_translations', $model->translationsModel()->getTable());
    }

    // public function setAttributeTranslations($attribute, array $translations, $save = false)
    // public function saveTranslations($translations)
    // public function hasTranslatorMethod($name)
    // public function getTranslatorMethod($name)
    // public function deleteAttributeTranslations(array $attributes, $locales = null)
    // public function deleteAttributeTranslation($attribute, $locales = null)
}
