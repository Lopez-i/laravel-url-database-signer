<?php

namespace lopez_i\UrlDatabaseSigner;

use Illuminate\Support\Facades\Facade;

class UrlSignerFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'url-database-signer';
    }
}