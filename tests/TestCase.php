<?php

namespace LaravelArab\Tarjama\Tests;

use CreateTranslationsTable;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use LaravelArab\Tarjama\Tests\Models\Article;
use LaravelArab\Tarjama\Tests\Models\Category;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
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
                $table->timestamps();
                $table->softDeletes();
            });
        });
    }

    protected function seedModels(...$modelClasses)
    {
        collect($modelClasses)->each(function (string $modelClass) {
            foreach (range(1, 0) as $index) {
                $modelClass::create([
                    'name' => "name {$index}",
                    'description' => "the long decription {$index}"
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
