<?php

namespace PVproject\Routing;

use GuzzleHttp\Psr7\ServerRequest;

class Router
{
    /** @var ServerRequest */
    private static $serverRequest;
    protected $domain = '';
    protected $fallback;
    protected $routes = [];

    public function __construct()
    {
        static::$serverRequest = static::$serverRequest ?? ServerRequest::fromGlobals();
    }

    /**
     * @return Router
     */
    public static function create(): Router
    {
        return new static();
    }

    /**
     * @return array
     */
    public function getRoutes(): array
    {
        return array_values($this->routes);
    }

    /**
     * Run the route matching.
     */
    public function run()
    {
        $request = static::$serverRequest;
        $pathParams = [];
        $action = $this->fallback;

        /** @var Route $route */
        foreach ($this->routes as $route) {
            if ($route->matches($request, $pathParams)) {
                $action = $route->getAction();
                break;
            }
        }

        if ($action) {
            $reflectionFunction = new \ReflectionFunction($action);
            $reflectionArguments = $reflectionFunction->getParameters();
            $arguments = [];
            $defaultArguments = compact('request');
            foreach ($reflectionArguments as $argument) {
                $argumentName = $argument->getName();
                if ($value = $pathParams[$argumentName] ?? null) {
                    $arguments[$argumentName] = $value;
                } elseif ($value = $defaultArguments[$argumentName]) {
                    $arguments[$argumentName] = $value;
                } else {
                    $arguments[$argumentName] = $argument->getDefaultValue();
                }
            }
            return call_user_func_array($action, $arguments);
        }

        return null;
    }

    /**
     * Set a domain for the new routes.
     *
     * @param string $domain
     *
     * @return Router
     */
    public function setDomain(string $domain = null): Router
    {
        $this->domain = preg_replace('/\/$/', '', '/'.preg_replace('/(^\/|\/$)/', '', (string) $domain));

        return $this;
    }

    /**
     * Set a fallback route.
     *
     * @param callable $action
     *
     * @return Router
     */
    public function setFallback(callable $action): Router
    {
        $this->fallback = $action;

        return $this;
    }

    /**
     * Add a route.
     * If a domain has been defined then it is prepended to the path.
     *
     * @param Route $route
     *
     * @return Router
     */
    public function addRoute(Route $route): Router
    {
        $route = $route->withPath($this->domain.$route->getPath());

        if ($name = $route->getName()) {
            $this->routes[$name] = $route;
        } else {
            $this->routes[] = $route;
        }

        return $this;
    }

    /**
     * Set a route.
     * If a domain has been defined then it is prepended to the path.
     *
     * @param string   $path
     * @param callable $action
     * @param array    $methods [optional]
     * @param string   $name    [optional]
     *
     * @return Route
     */
    public function setRoute(string $path, callable $action, array $methods = [], string $name = null): Route
    {
        $route = Route::create($this->domain.$path, $action, $methods, $name);

        if ($name = $route->getName()) {
            $this->routes[$name] = $route;
        } else {
            $this->routes[] = $route;
        }

        return $route;
    }
}
