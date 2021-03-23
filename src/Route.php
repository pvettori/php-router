<?php

namespace PVproject\Routing;

use Psr\Http\Message\RequestInterface;

class Route
{
    use RouteFactoryTrait;

    private static $allowedMethods = ['GET', 'PUT', 'POST', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];
    protected $action;
    protected $attributes = [];
    protected $methods = [];
    protected $middleware = [];
    protected $name;
    protected $path;
    protected $pathPattern;

    /**
     * @param string          $path    The route path.
     *                                 Path parameters must be enclosed in curly braces.
     * @param callable|string $action  A function (or invokable class) to call if the route matches the request.
     * @param array           $methods [optional] The methods that the route should match.
     *                                 If empty then the route matches any method.
     * @param string          $name    [optional] A name for the route.
     */
    public function __construct(string $path, $action, array $methods = [], string $name = null)
    {
        if (!is_callable($action) && !is_string($action)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid argument 2 for: %s(); expected calable or string, %s given',
                __METHOD__, is_object($action) ? get_class($action) : gettype($action)
            ));
        }

        $methods = array_map(function ($method) {
            return strtoupper((string) $method);
        }, $methods);

        if (!empty($methods) && array_diff($methods, static::$allowedMethods)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid argument 3 for: %s(); unexpected methods',
                __METHOD__
            ));
        }

        $this->action = $action;
        $this->methods = $methods;
        $this->name = $name;
        $this->path = $path;
    }

    /**
     * @return callable
     */
    public function getAction(): callable
    {
        $action = $this->action;
        if (is_callable($action)) {
            /* PASS */
        } elseif (is_string($action) && class_exists($action) && is_callable($instance = new $action())) {
            $action = $instance;
        } else {
            throw new \UnexpectedValueException(sprintf(
                'Unexpected value for: %s::$action; expected callable or class name, %s found',
                __CLASS__, is_object($action) ? get_class($action) : gettype($action)
            ));
        }

        /* @var callable $action */
        return $action;
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return array
     */
    public function getMetohds(): array
    {
        return $this->methods;
    }

    /**
     * @return array
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Check if current route matches given parameters.
     *
     * @param RequestInterface $request
     * @param array            $pathParams [optional] Array populated with found path parameters.
     *
     * @return boolean
     */
    public function matches(RequestInterface $request, array &$pathParams = null): bool
    {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();
        $pathParams = [];
        $pathPattern = sprintf('/^%s$/', static::parsePath($this->path));

        if (
            preg_match($pathPattern, urldecode($path), $matches) &&
            (empty($this->methods) || in_array($method, $this->methods))
        ) {
            $pathParams = array_filter($matches, function ($key) { return is_string($key); }, ARRAY_FILTER_USE_KEY);

            return true;
        }

        return false;
    }

    /**
     * Return a new instance with an added attributes.
     * Attributes are passed by name as arguments to the action.
     *
     * @param array $attributes An associative array containing attributes.
     *                          Attribute names must match this regex: ^[a-zA-Z_][a-zA-Z0-9_]*$.
     *
     * @return Route
     */
    public function withAttributes(array $attributes): Route
    {
        foreach ($attributes as $name => $value) {
            if (!is_string($name) || !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
                throw new \InvalidArgumentException(sprintf(
                    'Invalid argument 1 for: %s(); invalid attribute name found',
                    __METHOD__
                ));
            }
        }

        $clone = clone $this;
        $clone->attributes[$name] = $value;

        return $clone;
    }

    /**
     * Set route middleware.
     *
     * @param string|callable $middleware
     *
     * @return Route
     */
    public function withMiddleware($middleware): Route
    {
        $middleware = func_get_args();
        foreach ($middleware as &$handler) {
            $handler = (array) $handler;
            $handler = [
                'function' => array_shift($handler),
                'extra_arguments' => $handler,
            ];
            if (
                is_string($handler['function']) &&
                class_exists($handler['function']) &&
                method_exists($instance = new $handler['function'](), '__invoke')
            ) {
                $handler['function'] = $instance;
                continue;
            }
            if (is_callable($handler['function'])) {
                continue;
            }
            throw new \InvalidArgumentException(sprintf(
                'Invalid argument 1 for: %s(); expected array of handlers',
                __METHOD__
            ));
        }

        $clone = clone $this;
        $clone->middleware = $middleware;

        return $clone;
    }

    /**
     * Return a new instance of Route with the given name.
     *
     * @param string $name
     *
     * @return Route
     */
    public function withName(string $name): Route
    {
        $clone = clone $this;
        $clone->name = $name;

        return $clone;
    }

    /**
     * Return a new instance of Route with the given path.
     *
     * @param string $path
     *
     * @return Route
     */
    public function withPath(string $path): Route
    {
        $clone = clone $this;
        $clone->path = $path;

        return $clone;
    }

    /**
     * Convert a path pattern into a regex pattern with named matches.
     *
     * @param string $path
     *
     * @return string
     */
    protected static function parsePath(string $path): string
    {
        $segments = explode('/', $path);

        foreach ($segments as &$segment) {
            if (preg_match('/^\{(?<name>(?!this(\:|\}))[a-zA-Z_]\w*)(\:(?<pattern>(\\\\[\{\}\^\$]|[^\{\}\^\$\/])+))?\}$/', $segment, $matches)) {
                $segment = sprintf('(?<%s>%s)', $matches['name'], $matches['pattern'] ?? '[^\/]*');
            } else {
                $segment = preg_quote($segment, '/');
            }
        }

        return implode('\/', $segments);
    }
}
