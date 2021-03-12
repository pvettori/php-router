<?php

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use PVproject\Routing\Route;
use PVproject\Routing\Router;

class RouterTest extends TestCase
{
    public function testCanBeCreated()
    {
        $router = new Router();
        $this->assertInstanceOf(Router::class, $router);
    }

    public function testCanBeCreatedFromFactoryMethod()
    {
        $router = Router::create();
        $this->assertInstanceOf(Router::class, $router);
    }

    public function testCanAddRoute()
    {
        $router = Router::create()->addRoute(Route::get('/', function () {}));
        $this->assertCount(1, $router->getRoutes());
    }

    public function testCanSetRoute()
    {
        $router = new Router();
        $route = $router->setRoute('/', function () {});
        $this->assertInstanceOf(Route::class, $route);
    }

    /**
     * @runInSeparateProcess
     */
    public function testCanMatchRoute()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/some/other/path';

        $router = new Router();
        $router->setRoute('/some/path', function () { return 'not found'; });
        $router->setRoute('/some/other/path', function () { return 'not found'; }, ['POST']);
        $router->setRoute('/some/other/path', function () { return 'found'; });
        $this->assertEquals('found', $router->run());
    }

    /**
     * @runInSeparateProcess
     */
    public function testCanSetDomain()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/some/path';

        $router = new Router();
        $router->setDomain('/some');
        $router->setRoute('/some/path', function () { return 'not found'; });
        $router->setRoute('/path', function () { return 'found'; });
        $this->assertEquals('found', $router->run());
    }

    /**
     * @runInSeparateProcess
     */
    public function testCanSetFallback()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/fallback';

        $router = new Router();
        $router->setFallback(function () { return 'found'; });
        $router->setRoute('/some/path', function () { return 'not found'; });
        $router->setRoute('/some/other/path', function () { return 'not found'; });
        $this->assertEquals('found', $router->run());
    }

    /**
     * @runInSeparateProcess
     */
    public function testCanInjectArguments()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/some/path';

        $result = Router::create()
            ->addRoute(Route::get('/some/{param}', function ($param, $request) { return func_get_args(); }))
            ->run();
        $this->assertIsArray($result);
        $this->assertEquals('path', $result[0]);
        $this->assertInstanceOf(RequestInterface::class, $result[1]);
    }
}
