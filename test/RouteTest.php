<?php

use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use PVproject\Routing\Route;

require_once __DIR__.'/MiddlewareClass.php';

class RouteTest extends TestCase
{
    public function testCanBeCreated()
    {
        $route = new Route('/', function () {});
        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals('/', $route->getPath());
    }

    public function testCanBeCreatedWithFactoryMethodCreate()
    {
        $route = Route::create('/', function () {});
        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals([], $route->getMetohds());
    }

    public function testCanBeCreatedWithFactoryMethodGet()
    {
        $route = Route::get('/', function () {});
        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(['GET'], $route->getMetohds());
    }

    public function testCanSetAttributes()
    {
        $attributes = ['key' => 'value'];
        $route = Route::get('/', function () {})->withAttributes($attributes);
        $this->assertEquals($attributes, $route->getAttributes());
    }

    public function testCanSetFunctionAsMiddleware()
    {
        $route = Route::get('/', function () {})->withMiddleware(function () {});
        $this->assertIsCallable($route->getMiddleware()[0]['function']);
    }

    public function testCanSetFunctionNameAsMiddleware()
    {
        $route = Route::get('/', function () {})->withMiddleware('strtolower');
        $this->assertIsCallable($route->getMiddleware()[0]['function']);
    }

    public function testCanSetMiddlewareClassNameAsMiddleware()
    {
        $route = Route::get('/', function () {})->withMiddleware('\MiddlewareClass');
        $this->assertIsCallable($route->getMiddleware()[0]['function']);
    }

    public function testCanSetRouteNameImmutable()
    {
        $route = Route::get('/', function () {});
        $newRoute = $route->withName('route_name');
        $this->assertNull($route->getName());
        $this->assertEquals('route_name', $newRoute->getName());
    }

    public function testCanMatchRequest()
    {
        $request = new Request('GET', 'http://localhost/some/path');
        $route = Route::get('/some/path', function () {});
        $this->assertTrue($route->matches($request));
    }

    public function testCanMatchPathParameters()
    {
        $request = new Request('GET', 'http://localhost/some/path');
        $route = Route::get('/some/{param}', function () {});
        $this->assertTrue($route->matches($request, $pathParams));
        $this->assertEquals(['param' => 'path'], $pathParams);
    }

    public function testDoesNotMatchParameterThis()
    {
        $request = new Request('GET', 'http://localhost/some/path');
        $route = Route::get('/some/{this}', function () {});
        $this->assertFalse($route->matches($request, $pathParams));
    }

    public function testCanMatchPathParametersWithRegex()
    {
        $request = new Request('GET', 'http://localhost/some/path');
        $this->assertFalse(Route::get('/some/{param:\d+}', function () {})->matches($request));
        $this->assertTrue(Route::get('/some/{param:\w+}', function () {})->matches($request));
    }

    public function testDoesNotMatchParametersWithInvalidRegex()
    {
        $request = new Request('GET', 'http://localhost/some/^path');
        $this->assertFalse(Route::get('/some/{param:^\w*}', function () {})->matches($request));
        $this->assertTrue(Route::get('/some/{param:\^\w*}', function () {})->matches($request));
    }

    public function testThrowsExceptionOnInvalidMethod()
    {
        $this->expectException(InvalidArgumentException::class);
        $route = new Route('/', function () {}, ['INVALID']);
    }

    public function testNonParameterPathSegmentsAreEscaped()
    {
        $request = new Request('GET', 'http://localhost/some/path+');
        $this->assertTrue(Route::get('/some/path ', function () {})->matches($request));
    }
}
