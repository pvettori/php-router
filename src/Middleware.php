<?php

namespace PVproject\Routing;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

abstract class Middleware
{
    abstract public function __invoke(RequestInterface $request, callable $handler): ResponseInterface;
}
