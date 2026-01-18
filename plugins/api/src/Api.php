<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: CREATESAPPLICATIONTRAIT.PHP
 *  
 *  The software is a commercial product delivered under single, non-exclusive,
 *  non-transferable license for one domain or IP address. Therefore distribution,
 *  sale or transfer of the file in whole or in part without permission of Flynax
 *  respective owners is considered to be illegal and breach of Flynax License End
 *  User Agreement.
 *  
 *  You are not allowed to remove this information from the file without permission
 *  of Flynax respective owners.
 *  
 *  Flynax Classifieds Software 2024 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/

namespace Flynax\Api;

use Illuminate\Events\EventServiceProvider;
use Illuminate\Routing\RoutingServiceProvider;
use Illuminate\Support\Facades\Facade;

class Api extends \Illuminate\Container\Container
{
    /**
     * @var string - The API core version
     */
    const VERSION = '0.0.1';

    /**
     * @var string - The base path for the API installation
     */
    protected $basePath;

    /**
     * Create a new Flynax API instance.
     *
     * @param  string $basePath
     * @return void
     */
    public function __construct($basePath = null)
    {
        if ($basePath) {
            $this->setBasePath($basePath);
        }

        $this->registerBaseBindings();
        $this->registerBaseServiceProviders();

        // Tell facade about the application instance
        $this->registerFacades();
    }

    /**
     * Get the version number of the API.
     *
     * @return string
     */
    public function version()
    {
        return static::VERSION;
    }

    /**
     * Set the base path for the API.
     *
     * @param  string  $basePath
     * @return Flynax\Api\Api
     */
    public function setBasePath($basePath)
    {
        $this->basePath = rtrim($basePath, '\/');

        $this->bindPathsInContainer();

        return $this;
    }

    /**
     * Get the path to the API "src" directory.
     *
     * @param  string  $path Optionally, a path to append to the API path
     * @return string
     */
    public function path($path = '')
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'src' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Get the base path of the API.
     *
     * @param  string  $path Optionally, a path to append to the base path
     * @return string
     */
    public function basePath($path = '')
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Bind all of the API paths in the container.
     *
     * @return void
     */
    protected function bindPathsInContainer()
    {
        $this->instance('path', $this->path());
        $this->instance('path.base', $this->basePath());
    }

    /**
     * The application instance being facaded.
     *
     * @return void
     */
    protected function registerFacades()
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this);
    }

    /**
     * Register the basic bindings into the container.
     *
     * @return void
     */
    protected function registerBaseBindings()
    {
        static::setInstance($this);
    }

    /**
     * Register all of the base service providers.
     *
     * @return void
     */
    protected function registerBaseServiceProviders()
    {
        with(new EventServiceProvider($this))->register();
        with(new RoutingServiceProvider($this))->register();
    }
}
