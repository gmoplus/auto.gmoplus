<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : ROUTER.PHP
 *
 *	This script is a commercial software and any kind of using it must be
 *	coordinate with Flynax Owners Team and be agree to Flynax License Agreement
 *
 *	This block may not be removed from this file or any other files with out
 *	permission of Flynax respective owners.
 *
 *	Copyrights Flynax Classifieds Software | 2022
 *	https://www.flynax.com
 *
 ******************************************************************************/

namespace Flynax\Plugin\WordPressBridge;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;

/**
 * Class Router
 *
 * @since 2.0.0
 *
 * @package Flynax\Plugin\WordPressBridge
 */
class Router
{
    /**
     * @var array - Registered routes
     */
    protected $routes = array();

    /**
     * @var string - Request URI
     */
    protected $uri;

    /**
     * @var string - Request method
     */
    protected $method;

    /**
     * @var  object - Fast route lib dispatcher
     */
    protected $dispatcher;

    /**
     * Load and define file with routes
     *
     * @param string $file - Routes file full path
     *
     * @return static - Current class instance
     */
    public static function load($file)
    {
        $router = new static();
        $router->define(Request::uri(), Request::method());
        $dispatcher = \FastRoute\simpleDispatcher(function (RouteCollector $route) use ($file) {
            require $file;
        });


        $routeInfo = $dispatcher->dispatch($router->method, $router->uri);
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                Response::error('Route was not found', 404);
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                Response::error('Method is not allowed', 405);
                break;
            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];

                $handlerInfo = explode('@', $handler);
                $controllerName = $handlerInfo[0];
                $method = $handlerInfo[1];
                $controller = "\\Flynax\\Plugin\\WordPressBridge\\Controllers\\{$controllerName}";

                if (!method_exists($controller, $method)) {
                    Response::error(
                        sprintf(
                            "Method '%s()' was not found in controller '%s'",
                            $method,
                            $controllerName
                        ),
                        404
                    );
                    return;
                }

                if (method_exists($controller, $method)) {
                    $instance = new $controller();
                    $instance->{$method}();
                }

                break;
        }

        return $router;
    }

    /**
     * Set request URI and method
     *
     * @param string $uri    - Request URI
     * @param string $method - Request method
     *
     * @return self $this  - Instance of the current class
     */
    public function define($uri, $method)
    {
        $this->uri = $uri;
        $this->method = $method;

        return $this;
    }
}
