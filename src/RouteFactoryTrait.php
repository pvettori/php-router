<?php

namespace PVproject\Routing;

trait RouteFactoryTrait
{
    /**
     * Create a new Route.
     *
     * @param string   $path    The route path.
     *                          Path parameters must be enclosed in curly braces.
     * @param callable $action  A function to call if the route matches the request.
     * @param array    $methods [optional] The methods that the route should match.
     *                          If empty then the route matches any method.
     *
     * @return Route
     */
    public static function create(string $path, callable $action, array $methods = []): Route
    {
        return new Route($path, $action, $methods);
    }

    /**
     * Create a new Route with the "`GET`" method.
     *
     * @param string   $path   The route path.
     * @param callable $action A function to call if the route matches the request.
     *
     * @return Route
     */
    public static function get(string $path, callable $action): Route
    {
        return new Route($path, $action, ['GET']);
    }

    /**
     * Create a new Route with the "`PUT`" method.
     *
     * @param string   $path   The route path.
     * @param callable $action A function to call if the route matches the request.
     *
     * @return Route
     */
    public static function put(string $path, callable $action): Route
    {
        return new Route($path, $action, ['PUT']);
    }

    /**
     * Create a new Route with the "`POST`" method.
     *
     * @param string   $path   The route path.
     * @param callable $action A function to call if the route matches the request.
     *
     * @return Route
     */
    public static function post(string $path, callable $action): Route
    {
        return new Route($path, $action, ['POST']);
    }

    /**
     * Create a new Route with the "`PATCH`" method.
     *
     * @param string   $path   The route path.
     * @param callable $action A function to call if the route matches the request.
     *
     * @return Route
     */
    public static function patch(string $path, callable $action): Route
    {
        return new Route($path, $action, ['PATCH']);
    }

    /**
     * Create a new Route with the "`DELETE`" method.
     *
     * @param string   $path   The route path.
     * @param callable $action A function to call if the route matches the request.
     *
     * @return Route
     */
    public static function delete(string $path, callable $action): Route
    {
        return new Route($path, $action, ['DELETE']);
    }
}
