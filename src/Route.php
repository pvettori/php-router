<?php

namespace PVproject\Routing;

use Psr\Http\Message\RequestInterface;

class Route
{
    use RouteFactoryTrait;

    private static $allowedMethods = ['GET', 'PUT', 'POST', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];
    protected $action;
    protected $methods;
    protected $name;
    protected $path;
    protected $pathPattern;

    public function __construct(string $path, callable $action, array $methods = [], string $name = null)
    {
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
        return $this->action;
    }

    /**
     * @return array
     */
    public function getMetohds(): array
    {
        return $this->methods;
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
            preg_match($pathPattern, $path, $matches) &&
            (empty($this->methods) || in_array($method, $this->methods))
        ) {
            $pathParams = array_filter($matches, function ($key) { return is_string($key); }, ARRAY_FILTER_USE_KEY);

            return true;
        }

        return false;
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
        $chunks = explode('/', $path);

        foreach ($chunks as &$chunk) {
            if (preg_match('/^\{(?<name>[a-zA-Z_]\w*)(?:\:(?<pattern>(\\[\{\}\^\$]|[^\{\}\^\$])*))?\}$/', $chunk, $matches)) {
                $chunk = sprintf('(?<%s>%s?)', $matches['name'], $matches['pattern'] ?? '.*');
            }
        }

        return implode('\/', $chunks);
    }
}
