<?php

namespace LaravelArab\Tarjama\Tests;

use Illuminate\Support\Str;
use CreateTranslationsTable;
use LaravelArab\Tarjama\Tarjama;
use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\Collection;
use LaravelArab\Tarjama\Tests\Models\Article;
use LaravelArab\Tarjama\Tests\Models\Category;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use LaravelArab\Tarjama\Facades\Tarjama as TarjamaFacade;
use LaravelArab\Tarjama\Collection as TranslatorCollection;

abstract class TestCase extends OrchestraTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();

        // TODO: figure out a way to load this from the service provider
        $loader = AliasLoader::getInstance();
        $loader->alias('Tarjama', TarjamaFacade::class);

        $this->app->singleton('tarjama', function () {
            return new Tarjama();
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

        $this->createTables('articles', 'categories');
        $this->seedModels(Article::class, Category::class);
    }


    protected function createTranslationsTable()
    {
        include_once __DIR__ . '/../publishable/database/migrations/create_translations_table.php.stub';

        (new CreateTranslationsTable())->up();
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
            foreach (range(1, 0) as $index) {
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
