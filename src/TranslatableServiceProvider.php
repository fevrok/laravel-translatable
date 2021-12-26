<?php

namespace Fevrok\Translatable;

use Fevrok\Translatable\Collection as TranslatorCollection;
use Fevrok\Translatable\Facades\Translatable as TranslatableFacade;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

class TranslatableServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../publishable/config/translatable.php' => config_path('translatable.php'),
        ]);

        if (! class_exists('CreateTranslationsTable')) {
            $timestamp = date('Y_m_d_His', time());

            $this->publishes([
                __DIR__.'/../publishable/database/migrations/create_translations_table.php.stub' => database_path("/migrations/{$timestamp}_create_translations_table.php"),
            ], 'migrations');
        }

        Collection::macro('translate', function () {
            $transtors = [];

            foreach ($this->all() as $item) {
                $transtors[] = call_user_func_array([$item, 'translate'], func_get_args());
            }

            return new TranslatorCollection($transtors);
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../publishable/config/translatable.php', 'translatable');

        $loader = AliasLoader::getInstance();
        $loader->alias('Translatable', TranslatableFacade::class);

        $this->app->singleton('translatable', function () {
            return new Translatable();
        });
    }
}
