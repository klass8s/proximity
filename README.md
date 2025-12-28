# Create PHP proxy objects with ease

## Motivation
This library is inspired by the brilliant
[Ocramius/ProxyManager](https://github.com/Ocramius/ProxyManager) library.
The mentioned library is lacking some features for now,
such as the ability to create proxies from the class instances with non-nullable
typed properties. Unfortunately, this great library is not actively supported,
therefore `tequila/proximity` library tries to implement 
some of `ProxyManager`'s functionality.

## Usage

You can create proxy instances by using `ProxyFactory` class:

```php
<?php

$factory = new \Proximity\ProxyFactory(
    new \Proximity\ProxyGenerator(),
    '/path/where/proxy/classes/will/be/stored'
);

$myObject = new Foo();
$proxy = $factory->proxy($myObject);
```

You may also want to intercept calls to object's methods before or after
the method body was executed. To do that, use *interceptors*:

```php
use Proximity\ProxyFactory;
use Proximity\ProxyGenerator;
use Proximity\MethodsInterception\BodyInterceptorInterface;
use Proximity\ProxyInterface;
use Proximity\MethodsInterception\BodyInterceptionResult;
use Proximity\ProxyOptions;
use Proximity\MethodsInterception\ResultInterceptorInterface;
use Proximity\MethodsInterception\InterceptionContext;

class Foo
{
    public function bar(): void
    {
        echo 'Bar!', PHP_EOL;
    }
}

$factory = new ProxyFactory(
    new ProxyGenerator(),
    '/path/where/proxy/classes/will/be/stored'
);

$bodyInterceptor = new class implements BodyInterceptorInterface 
{
    public function beforeMethodBody(ProxyInterface $proxy, object $object, string $methodName, array $methodArgs) : BodyInterceptionResult
    {
        echo "Method $methodName() is about to be executed!\n";
        
        return new BodyInterceptionResult(preventMethodBody: false);
    }
};

$resultInterceptor = new class implements ResultInterceptorInterface
{
    public function afterMethodBody(ProxyInterface $proxy, object $object, string $methodName, array $methodArgs, InterceptionContext $context): void
    {
        echo "Method $methodName() just finished execution!\n";
    }
};

$foo = new Foo();
$proxy = $factory->proxy(
    $foo,
    new ProxyOptions(
        ['bar' => [$bodyInterceptor]],
        ['bar' => [$resultInterceptor]],
    )
);

$proxy->bar();
```

The following output will be generated:

```
Method bar() is about to be executed!
Bar!
Method bar() just finished execution!
```

You can also prevent method body execution from *body interceptors* by providing `true`
as a first argument to `BodyInterceptionResult` constructor. If you're preventing the body
execution, you may also provide return value for the method, by passing it as a second
parameter to `BodyInterceptionResult` constructor.

To replace method's return value from *result interceptors*, use `$context` argument:

```php
public function afterMethodBody(ProxyInterface $proxy, object $object, string $methodName, array $methodArgs, InterceptionContext $context): void
{
    // do some stuff, then:
    $context->returnValue = 'New value';
}
```
