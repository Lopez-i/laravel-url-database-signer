<?php
namespace lopez_i\UrlDatabaseSigner;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use File;

class UrlDatabaseSignerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        if ($this->app->runningInConsole())
        {
            $source_config = __DIR__.'/config/url-database-signer.php';
            $source_db = __DIR__.'/Migration/2017_07_22_144134_create_signed_urls_table.php';
            $source_middleware = __DIR__.'/Middleware/ValidateUrlDatabaseSignature.php';
            $source_job = __DIR__.'/Jobs/RemoveExpiredSignedUrl.php';

            File::makeDirectory(base_path('./app/Jobs', 0775));
            $this->publishes(
                [
                    $source_config => config_path('url-database-signer.php'),
                    $source_db => base_path('./database/migrations/2017_07_22_144134_create_signed_urls_table.php'),
                    $source_middleware => base_path('./app/Http/Middleware/ValidateUrlDatabaseSignature.php'),
                    $source_job => base_path('./app/Jobs/RemoveExpiredSignedUrl.php')
                ]);

            $this->mergeConfigFrom($source_config, 'url-database-signer');
        }
    }
}