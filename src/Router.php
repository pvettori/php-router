<?php

namespace PVproject\Routing;

use GuzzleHttp\Psr7\ServerRequest;

class Router
{
    /** @var ServerRequest */
    private static $serverRequest;
    protected $fallback;
    protected $prefix = '';
    protected $routes = [];

    /**
     * Create a new instance of Router.
     *
     * @param array $config [optional]
     */
    public function __construct(array $config = [])
    {
        static::$serverRequest = static::$serverRequest ?? ServerRequest::fromGlobals();

        if (is_callable($fallback = $config['fallback'] ?? null)) {
            $this->fallback = $fallback;
        }

        if (is_string($prefix = $config['prefix'] ?? null)) {
            $this->prefix = $prefix;
        }
    }

    /**
     * @return Router
     */
    public static function create(array $config = []): Router
    {
        return new static($config);
    }

    /**
     * Retrieve a named route.
     *
     * @param string $name The route name.
     *
     * @return Route|null
     */
    public function getRoute(string $name): ?Route
    {
        return $this->routes[$name] ?? null;
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
     *
     * @param array $arguments [optional] Associative array of arguments injected into the action function.
     *                         Route attributes and path parameters are also injected as arguments.
     *                         Route attributes have precendence over run arguments.
     *                         Path parameters have precendence over Route attributes and run arguments.
     */
    public function run(array $arguments = [])
    {
        $action = $this->fallback;
        $pathParams = [];
        $request = static::$serverRequest;

        /** @var Route $route */
        foreach ($this->routes as $route) {
            if ($route->matches($request, $pathParams)) {
                $action = $route->getAction();
                $arguments = array_merge($arguments, $route->getAttributes());
                break;
            }
        }
        $arguments = array_merge($arguments, $pathParams);

        if ($action) {
            $middleware = [];
            $arguments = array_merge($arguments, compact('request'));
            if (isset($route)) {
                $arguments['route'] = $route;
                $middleware = $route->getMiddleware();
            }

            if ($middleware) {
                $middleware = array_reverse(array_values($middleware));
                foreach ($middleware as $index => &$handler) {
                    $next = $middleware[$index - 1]['function'] ?? function ($request) use ($action, $arguments) {
                        return $action(...static::prepareNamedArguments($action, array_merge($arguments, compact('request'))));
                    };
                    $handler['function'] = function ($request) use ($handler, $next) {
                        return $handler['function']($request, $next, ...$handler['extra_arguments']);
                    };
                }
                $action = end($middleware)['function'];
                $arguments = compact('request');
            }

            $response = static::callWithNamedArguments($action, $arguments);
        }

        return $response ?? null;
    }

    /**
     * Add routes grouped by prefix.
     *
     * @param array  $routes
     * @param string $prefix
     * @param array  $middleware [optional]
     *
     * @return Router
     */
    public function addRouteGroup(string $prefix, array $routes, array $middleware = null): Router
    {
        $prefix = preg_replace('/\/$/', '', '/'.preg_replace('/(^\/|\/$)/', '', $prefix));

        foreach ($routes as $name => $route) {
            if (is_string($name)) {
                $route = $route->withName($name);
            }

            if ($prefix !== '') {
                $route = $route->withPath($prefix.$route->getPath());
            }

            if ($middleware) {
                $route = $route->withMiddleware($middleware);
            }

            $this->addRoute($route);
        }

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
        $route = $route->withPath($this->prefix.$route->getPath());

        if ($name = $route->getName()) {
            $this->routes[$name] = $route;
        } else {
            $this->routes[] = $route;
        }

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
     * Set a prefix for the new routes.
     *
     * @param string $prefix
     *
     * @return Router
     */
    public function setPrefix(string $prefix = null): Router
    {
        $this->prefix = preg_replace('/\/$/', '', '/'.preg_replace('/(^\/|\/$)/', '', (string) $prefix));

        return $this;
    }

    /**
     * Set a route.
     * If a domain has been defined then it is prepended to the path.
     *
     * @param string   $path    The route path.
     *                          Path parameters must be enclosed in curly braces.
     * @param callable $action  A function to call if the route matches the request.
     * @param array    $methods [optional] The methods that the route should match.
     *                          If empty then the route matches any method.
     * @param string   $name    [optional] A name for the route.
     *
     * @return Route
     */
    public function setRoute(string $path, callable $action, array $methods = [], string $name = null): Route
    {
        $route = Route::create($this->prefix.$path, $action, $methods, $name);

        if ($name = $route->getName()) {
            $this->routes[$name] = $route;
        } else {
            $this->routes[] = $route;
        }

        return $route;
    }

    /**
     * Call a function with arguments in the correct order.
     *
     * @param string|callable $function   The function that recieves the arguments.
     * @param array           $parameters [optional] An associative array of possible function arguments.
     *
     * @return mixed
     */
    private static function callWithNamedArguments($function, array $parameters = [])
    {
        return call_user_func_array($function, static::prepareNamedArguments($function, $parameters));
    }

    /**
     * Prepare a function's arguments in the correct order.
     *
     * @param string|callable $function   The function that recieves the arguments.
     * @param array           $parameters [optional] An associative array of possible function arguments.
     *
     * @return array
     */
    private static function prepareNamedArguments($function, array $parameters = []): array
    {
        $reflectionFunction = new \ReflectionFunction($function);
        $arguments = [];
        foreach ($reflectionFunction->getParameters() as $argument) {
            $argumentName = $argument->getName();
            $arguments[$argumentName] = $parameters[$argumentName] ?? $argument->getDefaultValue();
        }

        return array_values($arguments);
    }
}
