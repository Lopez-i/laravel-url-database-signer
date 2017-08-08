<?php
namespace lopez_i\UrlDatabaseSigner;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;

class UrlSignerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        $source_config = realpath(__DIR__.'/../config/url-database-signer.php');
        $source_db = realpath(__DIR__.'/../Migration/2017_07_22_144134_create_signed_urls_table.php');
        $source_middleware = realpath(__DIR__.'/../Middleware/ValidateUrlSignature.php');
        $this->publishes(
            [
                $source_config => config_path('url-database-signer.php'),
                $source_db => database_path('2017_07_22_144134_create_signed_urls_table.php'),
                $source_middleware => base_path('./app/Http/Middleware')
            ]);
        $this->mergeConfigFrom($source_config, 'url-database-signer.php');
    }
}