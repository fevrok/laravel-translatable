<?php

namespace Fevrok\Translatable\Tests;

use CreateTestsTranslationsTable;
use CreateTranslationsTable;
use Fevrok\Translatable\Collection;
use Fevrok\Translatable\HasTranslations;
use Fevrok\Translatable\Tests\Models\NotTranslatableModel;
use Fevrok\Translatable\Tests\Models\TestsTranslationsModel;
use Fevrok\Translatable\TranslatableServiceProvider;
use Fevrok\Translatable\Translator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function getPackageProviders($app)
    {
        return [TranslatableServiceProvider::class];
    }

    protected function setUpDatabase()
    {
        $this->createTranslationsTable();

        $this->createTables('articles');
        $this->seedModels(TranslatableModel::class);
    }

    protected function createTranslationsTable()
    {
        include_once __DIR__ . '/../publishable/database/migrations/create_translations_table.php.stub';
        include_once __DIR__ . '/stubs/create_tests_translations_table.php.stub';

        (new CreateTranslationsTable())->up();
        (new CreateTestsTranslationsTable())->up();
    }

    protected function createTables(...$tableNames)
    {
        collect($tableNames)->each(function (string $tableName) {
            Schema::create($tableName, function (Blueprint $table) use ($tableName) {
                $table->increments('id');
                $table->string('name');
                $table->text('description');
                $table->string('slug');
                $table->timestamps();
                $table->softDeletes();
            });
        });
    }

    protected function seedModels(...$modelClasses)
    {
        collect($modelClasses)->each(function (string $modelClass) {
            foreach (range(1, 2) as $index) {
                $name = "name {$index}";

                $modelClass::create([
                    'name' => $name,
                    'description' => "the long decription {$index}",
                    'slug' => Str::slug($name),
                ]);
            }
        });
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('sqlite');
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        // $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('app.key', 'base64:' . base64_encode(
            Encrypter::generateKey($app['config']['app.cipher'])
        ));
    }

    /**
     * @param string $model
     * @param string $locale
     * @return Translator
     */
    public function getModelTranslator(string $model, string $locale): Translator
    {
        return $model::first()->translate($locale);
    }

    /**
     * @param string $model
     * @param string $locale
     * @return Collection
     */
    public function getModelTranslatorCollection(string $model, string $locale): Collection
    {
        return $model::get()->translate($locale);
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
