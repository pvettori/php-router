<?php

namespace PVproject\Routing;

trait RouteFactoryTrait
{
    public static function create(string $path, callable $action, array $methods = []): Route
    {
        return new Route($path, $action, $methods);
    }

    public static function get(string $path, callable $action): Route
    {
        return new Route($path, $action, ['GET']);
    }

    public static function put(string $path, callable $action): Route
    {
        return new Route($path, $action, ['PUT']);
    }

    public static function post(string $path, callable $action): Route
    {
        return new Route($path, $action, ['POST']);
    }

    public static function patch(string $path, callable $action): Route
    {
        return new Route($path, $action, ['PATCH']);
    }

    public static function delete(string $path, callable $action): Route
    {
        return new Route($path, $action, ['DELETE']);
    }
}
