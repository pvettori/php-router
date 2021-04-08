<?php

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use PVproject\Routing\Middleware;

class MiddlewareClass extends Middleware
{
    public function __invoke(RequestInterface $request, callable $handler): ResponseInterface
    {
        return $handler($request);
    }
}
