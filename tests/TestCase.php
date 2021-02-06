<?php

namespace Fevrok\Translatable\Tests;

use CreateTestsTranslationsTable;
use CreateTranslationsTable;
use Fevrok\Translatable\Collection as TranslatorCollection;
use Fevrok\Translatable\Facades\Translatable as TranslatableFacade;
use Fevrok\Translatable\Translatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();

        // TODO: figure out a way to load this from the service provider
        $loader = AliasLoader::getInstance();
        $loader->alias('Translatable', TranslatableFacade::class);

        $this->app->singleton('translatable', function () {
            return new Translatable();
        });

        Collection::macro('translate', function () {
            $transtors = [];

            foreach ($this->all() as $item) {
                $transtors[] = call_user_func_array([$item, 'translate'], func_get_args());
            }

            return new TranslatorCollection($transtors);
        });
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
}
