# Router
[![Latest Version](https://img.shields.io/badge/version-0.0-orange)]()
[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.1-blue)]()
[![MIT License](https://img.shields.io/badge/license-MIT-green)](https://github.com/pvettori/router/blob/master/LICENSE)

A simple router utility for applications.  

Web applications are, in their essence, software that respond to an HTTP request.  
This simple router offers a quick and easy way to define the routes of your application.  

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

Or have access to the request object in your route action (the router handles a PSR-7 Request object):
```php
$route = Route::get('/path', function ($request) {
    echo 'This is the HTTP method: '.$request->getMethod();
});
```
> Note that any action function argument named `$request` gets automatically assigned the server request object.

And maybe define a domain for your subsequent routes:
```php
$router = Router::create()->setDomain('/admin');
$router->setRoute('/login' function () {
    echo 'This route is /admin/login';
});
```

## Reference
### PVproject\Routing\Route
A class representing a single route.  
The Route object is immutable.
#### **Route** Methods
##### `__construct(string $path, callable $action, array $methods = [], string $name = null)`
Create a new Route.
|Argument|Type|Description|
|:-|:-|:-|
|`$path`|*string*|The route path.<br>Path parameters can be declared with braces (ex.: "`/path/{param}`").<br>Path parameters can also be restricted by appending a colon and a regex to the parameter name (ex.: "`/path/{param:\d+}`").<br>*NOTE: The regex does not accept the `{` , `}` , `^` and `$` metacharacters.* |
|`$action`|*callable*|A function that gets executed if the route matches the current server request.|
|`$methods`|*array*|Optional.<br>An array of HTTP request methods.<br>Valid methods are: "`GET`", "`PUT`", "`POST`", "`PATCH`", "`DELETE`", "`HEAD`", "`OPTIONS`". If not declared then the route matches any method.|
|`$name`|*string*|Optional.<br>A name for the route.|
##### `getAction(): callable`
Returns the route action function.
##### `getMetohds(): array`
Returns the route methods.
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
##### `Route::create(string $path, callable $action, array $methods = [])`
Create a new Route.
##### `Route::get(string $path, callable $action)`
Create a new Route with the "`GET`" method.
##### `Route::put(string $path, callable $action)`
Create a new Route with the "`PUT`" method.
##### `Route::post(string $path, callable $action)`
Create a new Route with the "`POST`" method.
##### `Route::patch(string $path, callable $action)`
Create a new Route with the "`PATCH`" method.
##### `Route::delete(string $path, callable $action)`
Create a new Route with the "`DELETE`" method.

### PVproject\Routing\Router
The router class.
#### **Router** Methods
##### `__construct()`
Create a new Router.
##### `getRoutes(): array`
Get the defined routes.
##### `run()`
Run the router.
##### `setDomain(string $domain = null): Router`
Set a restricted domain. The domain is prepended to the path every subsequent route.  
Routes delcared prior to this method are not affected.
|Argument|Type|Description|
|:-|:-|:-|
|`$domain`|*string*|Optional.<br>The domain.<br>*NOTE: using `->setDomain()` without argument resets the domain.*|
##### `setFallback(callable $action): Router`
Set an action function that gets called if no route match is found.
|Argument|Type|Description|
|:-|:-|:-|
|`$action`|*callable*|The action function.|
##### `addRoute(Route $route): Router`
Add a Route.
|Argument|Type|Description|
|:-|:-|:-|
|`$route`|*Route*|The Route object.|
##### `setRoute(string $path, callable $action, array $methods = [], string $name = null): Route`
Adds a route and returns the Route object.  
See [Route::__construct()](#route-methods) for details.
#### **Router** Factory Methods
##### `Router::create()`
Create a new Router.
