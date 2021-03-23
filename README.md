# Router
[![Latest Version](https://img.shields.io/badge/version-0.1.2-orange)](https://github.com/pvettori/router/releases)
![PHP Version](https://img.shields.io/badge/php-%3E%3D7.1-blue)
[![MIT License](https://img.shields.io/badge/license-MIT-green)](https://github.com/pvettori/router/blob/master/LICENSE)

A simple router utility for applications.  

Web applications are, in their essence, software that respond to an HTTP request.  
This simple router offers a quick and easy way to define the routes of your application.  

## Contents
1. [Quick start](#quick-start)  
    1a. [Installation](#installation)  
    1b. [Use example](#use-example)  
2. [Usage](#usage)  
3. [Advanced Usage](#advanced-usage)  
4. [Reference](#reference)  
    4a. [PVproject\Routing\Middleware](#pvproject\routing\middleware)  
    4b. [PVproject\Routing\Route](#pvproject\routing\route)  
    4a. [PVproject\Routing\Router](#pvproject\routing\router)  

## Quick start
### Installation
```
composer require pvettori/router
```

### Use example
```php
use PVproject\Routing\Route;
use PVproject\Routing\Router;

Router::create()->addRoute(Route::get('/home', function () {
    http_response_code(200);
    exit('<h1>Home</h1>');
}))->run();
```

## Usage
You can create a route:
```php
$route = Route::get('/home', function () { echo 'This is the home page.'; });
```
Then add it to a router:
```php
$router = Router::create()->addRoute($route);
```
And finally run the router:
```php
$router->run();
```
You may want to define a route with path parameters:
```php
$route = Route::get('/path/{param}', function ($param) {
    echo 'This is the route parameter: '.$param;
});
```
> Note that path parameters are automatically passed as arguments with the same name to the action function (in no particular order).

> Path parameters can also be injected as associative array through the `$parameters` argument.

Or have access to the request object in your route action (the router handles a PSR-7 Request object):
```php
$route = Route::get('/path', function ($request) {
    echo 'This is the HTTP method: '.$request->getMethod();
});
```
> Note that any action function argument named `$request` gets automatically assigned the server request object.

And maybe define a path prefix for your subsequent routes:
```php
$router = Router::create()->setPrefix('/admin');
$router->setRoute('/login' function () {
    echo 'This route is /admin/login';
});
```

## Advanced Usage
The Route `$action` argument accepts the name of an invokable class (*a class with the magic method `__invoke()`*).  
```php
$route = Route::get('/path', \InvokableClass::class);
$route = Router::create()->setRoute('/', \InvokableClass::class, ['GET']);
```
A Route can have middleware assigned to it.  
Middleware functions and classes must all accept at least two arguments, the first being the server request (modified by the previous middleware) and the second being the next handler.
```php
function middleware_function($request, $handler) {
    // code executed before next handler...
    $response = $handler($request);
    // code executed after next handler...

    return $response;
}

class MiddewareClass extends \PVproject\Routing\Middleware {
    public function __invoke(
        RequestInterface $request,
        callable $handler
    ): ResponseInterface {
        // code executed before next handler...
        $response = $handler($request);
        // code executed after next handler...

        return $response;
    }
}

$route = Route::create('/path', function ($request) { return $response; })
    ->withMiddleware(
        'middleware_function',
        'MiddewareClass'
    );
```
Middleware may require extra arguments.  
Those arguments can be passed by entering the middleware as an array with the first item being the middleware function or class and the subsequent items being the extra arguments in exact order.
```php
function middleware_function($request, $handler, $extra) {
    return $response;
}

$route = Route::create('/path', function ($request) { return $response; })
    ->withMiddleware(
        ['middleware_function', 'extra_argument'],
        'MiddewareClass'
    );
```
Routes can also be grouped by prefix:
```php
$router = Router::create()->addRouteGroup('/admin', [
    Route::create('/login', function ($request) { return $response; })
    Route::create('/home', function ($request) { return $response; })
], [
    ['middleware_function', 'extra_argument'],
    'MiddewareClass'
]);
```
Extra arguments can be provided to the router.  
Such arguments are automatically injected in the route action.
```php
// arguments passed on Router creation
$router = Router::create([
    'arguments' => [
        'extra1' => 'value1',
        'extra2' => 'value2',
    ]
]);
// arguments passed on Router run
$router = Router::create()->run([
    'extra1' => 'value1',
    'extra2' => 'value2',
]);
```

## Reference

### PVproject\Routing\Middleware
Abstract class to be extend in order to help create middleware classes.

#### **Middleware** Methods
##### `__invoke(RequestInterface $request, callable $handler): ResponseInterface`
The only required method of a Middleware class.
|Argument|Type|Description|
|:-|:-|:-|
|`$request`|*RequestInterface*|The server request object (modified by previous middleware).|
|`$handler`|*callable*|The next middleware or route action.|

### PVproject\Routing\Route
A class representing a single route.  
The Route object is immutable.

#### **Route** Methods
##### `__construct(string $path, $action, array $methods = [], string $name = null)`
Create a new Route.
|Argument|Type|Description|
|:-|:-|:-|
|`$path`|*string*|The route path.<br>Path parameters can be declared with braces (ex.: "`/path/{param}`").<br>*NOTE: Parameter names start with a letter or underscore, followed by any number of letters, numbers, or underscores.*<br>Path parameters can also be restricted by appending a colon and a regex to the parameter name (ex.: "`/path/{param:\d+}`"). <br>*NOTE: The prameter name cannot be "this" as it would be injected as the reserved word `$this`.* <br>*NOTE: The regex does not accept the `/` character and the `{` , `}` , `^` and `$` metacharacters.* |
|`$action`|*mixed*|A function, function name or invokable class name that gets executed if the route matches the current server request.|
|`$methods`|*array*|Optional.<br>An array of HTTP request methods.<br>Valid methods are: "`GET`", "`PUT`", "`POST`", "`PATCH`", "`DELETE`", "`HEAD`", "`OPTIONS`". If not declared then the route matches any method.|
|`$name`|*string*|Optional.<br>A name for the route.|
##### `getAction(): callable`
Returns the route action function.
##### `getAttributes(): array`
Returns the route attributes.
##### `getMetohds(): array`
Returns the route methods.
##### `getMiddleware(): array`
Returns the route middleware.
##### `getName(): ?string`
Returns the route name.
##### `getPath(): string`
Returns the declared route path.
##### `matches(RequestInterface $request, array &$pathParams = null): bool`
Check if the route matches a given request object.
|Argument|Type|Description|
|:-|:-|:-|
|`$request`|*RequestInterface*|A request object.|
|`&$pathParams`|*array*|Optional.<br>An array populated with the defined path parameters.|
##### `withAttributes(array $attributes): Route`
Return a new instance with an added attributes.  
Attributes are passed by name as arguments to the action.
|Argument|Type|Description|
|:-|:-|:-|
|`$attributes`|*array*|An associative array containing attributes.<br>Attribute names must match this regex: `^[a-zA-Z_][a-zA-Z0-9_]*$`.|
##### `withMiddleware($middleware [, $middleware] [, ...])`
Returns a new Route object with middleware assigned to it.
|Argument|Type|Description|
|:-|:-|:-|
|`$middleware`|*string\|callable*|A middleware class or function.<br>If extra arguments need to be passed to the middleware then the definition can be expressed as an array with the first argument being the middleware class or function and the subsequent arguments being the extra arguments in exact order.|
##### `withName(string $name): Route`
Returns a new Route object with the specified name.
|Argument|Type|Description|
|:-|:-|:-|
|`$name`|*string*|The route name.|
##### `withPath(string $path): Route`
Returns a new Route object with the specified path.
|Argument|Type|Description|
|:-|:-|:-|
|`$path`|*string*|The route path.<br>See [Route::__construct()](#route-methods) for details.|

#### **Route** Factory Methods
##### `Route::create(string $path, $action, array $methods = [])`
Create a new Route.
##### `Route::get(string $path, $action)`
Create a new Route with the "`GET`" method.
##### `Route::put(string $path, $action)`
Create a new Route with the "`PUT`" method.
##### `Route::post(string $path, $action)`
Create a new Route with the "`POST`" method.
##### `Route::patch(string $path, $action)`
Create a new Route with the "`PATCH`" method.
##### `Route::delete(string $path, $action)`
Create a new Route with the "`DELETE`" method.

### PVproject\Routing\Router
The router class.

#### **Router** Methods
##### `__construct(array $config = [])`
Create a new Router.
|Argument|Type|Description|
|:-|:-|:-|
|`$config`|*array*|A configuration array.<br>Available configuration options: `arguments`, `fallback`, `prefix`.|
##### `getRoute(string $name): ?Route`
Get the a named route.
##### `getRoutes(): array`
Get the defined routes.
##### `run(array $arguments = [])`
Run the router.
|Argument|Type|Description|
|:-|:-|:-|
|`$arguments`|*array*|An associative array of extra arguments injected into the action function.<br>Arguments injected by default are: `$parameters`, `$request`, `$route`.|
##### `setPrefix(string $prefix = null): Router`
Set a route prefix. The prefix is prepended to the path of every subsequent route.  
Routes delcared prior to this method are not affected.
|Argument|Type|Description|
|:-|:-|:-|
|`$prefix`|*string*|Optional.<br>The path prefix.<br>*NOTE: calling `->setPrefix()` without argument removes the prefix.*|
##### `setFallback($action): Router`
Set an action that gets executed when no route match is found.
|Argument|Type|Description|
|:-|:-|:-|
|`$action`|*mixed*|The fallback action.|
##### `addRoute(Route $route): Router`
Add a Route.
|Argument|Type|Description|
|:-|:-|:-|
|`$route`|*Route*|The Route object.|
##### `addRouteGroup(string $prefix, array $routes, array $middleware = null): Router`
Add multiple routes grouped by prefix.  
Previously declared prefixes are prepended to the group prefix.  
Subsequent routes are not affected by the group prefix.
|Argument|Type|Description|
|:-|:-|:-|
|`$prefix`|*string*|The grouped routes prefix.|
|`$routes`|*array*|The grouped routes.|
|`$middleware`|*array*|An array of middleware applied to all routes in the group.|
##### `run(array $arguments = [])`
Run the route matching.
|Argument|Type|Description|
|:-|:-|:-|
|`$arguments`|*array*|Associative array of arguments injected into the action function. Route attributes and path parameters are also injected as arguments.<br>Route attributes have precendence over run arguments.<br>Path parameters have precendence over Route attributes and run arguments.|
##### `setRoute(string $path, $action, array $methods = [], string $name = null): Route`
Adds a route and returns the Route object.  
See [Route::__construct()](#route-methods) for details.

#### **Router** Factory Methods
##### `Router::create()`
Create a new Router.
