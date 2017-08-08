<?php
namespace lopez_i\UrlDatabaseSigner;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;

class UrlDatabaseSignerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        if ($this->app->runningInConsole())
        {
            $source_config = __DIR__.'/../config/url-database-signer.php';
            $source_db = __DIR__.'/../Migration/2017_07_22_144134_create_signed_urls_table.php';
            $source_middleware = __DIR__.'/../Middleware/ValidateUrlSignature.php';
            $this->publishes(
                [
                    $source_config => $this->app->config_path('url-database-signer.php'),
                ]);
            $this->publishes(
                [
                    $source_db => $this->app->database_path('2017_07_22_144134_create_signed_urls_table.php'),
                ]);
            $this->publishes(
                [
                    $source_middleware => $this->app->base_path('./app/Http/Middleware')
                ]);
            $this->mergeConfigFrom($source_config, 'url-database-signer.php');
        }
    }
}