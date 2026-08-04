# Create PHP proxy objects with ease

📖 **Full documentation lives in [`docs/`](docs/index.md)** (published on the Tequila Platform website).

## Motivation

This library is inspired by the brilliant
[Ocramius/ProxyManager](https://github.com/Ocramius/ProxyManager) library.
The mentioned library is lacking some features for now,
such as the ability to create proxies from class instances with non-nullable
typed properties. Unfortunately, this great library is not actively supported,
therefore `tequila/proximity` tries to implement
some of `ProxyManager`'s functionality.

## Usage

A `ProxyFactory` takes a generator and a **proxy strategy** — the strategy decides how
generated proxy classes are loaded. `EvaluatingStrategy` loads them with `eval()`
(nothing touches disk — the default choice, a once-per-worker cost under FrankenPHP
worker mode); `SaveToFileStrategy` writes them to a directory for inspection and
debugging:

```php
use Proximity\ProxyFactory;
use Proximity\ProxyGenerator;
use Proximity\ProxyStrategy\EvaluatingStrategy;
use Proximity\ProxyStrategy\SaveToFileStrategy;

$factory = new ProxyFactory(new ProxyGenerator(), new EvaluatingStrategy());
// or:
$factory = new ProxyFactory(new ProxyGenerator(), new SaveToFileStrategy('/path/for/proxy/classes'));

$proxy = $factory->proxy(new Foo());
```

The proxied class must not be `final` — the proxy is a generated subclass. Each proxy
class is generated once per factory and reused for subsequent instances.

### Intercepting method calls

*Body interceptors* run before the method body and may veto it; *result interceptors*
run after it and may rewrite the return value. Both are wired per-method (or as
defaults for every method) via `ProxyOptions`:

```php
use Proximity\MethodsInterception\BodyInterceptionResult;
use Proximity\MethodsInterception\BodyInterceptorInterface;
use Proximity\MethodsInterception\InterceptionContext;
use Proximity\MethodsInterception\ResultInterceptorInterface;
use Proximity\ProxyInterface;
use Proximity\ProxyOptions;

class Foo
{
    public function bar(string $who = 'world'): string
    {
        return "Bar for {$who}!";
    }
}

$logger = new class implements BodyInterceptorInterface, ResultInterceptorInterface {
    public function beforeMethodBody(ProxyInterface $proxy, object $object, string $methodName, array $methodArgs): BodyInterceptionResult|null
    {
        // $methodArgs is a named map: parameter name => value, declared defaults applied
        echo "→ {$methodName}(" . json_encode($methodArgs) . ")\n";

        return null; // null: proceed normally
    }

    public function afterMethodBody(ProxyInterface $proxy, object $object, string $methodName, array $methodArgs, InterceptionContext $context): void
    {
        echo "← {$methodName} returned " . var_export($context->returnValue, true) . "\n";
    }
};

$proxy = $factory->proxy(new Foo(), new ProxyOptions(
    bodyInterceptors: ['bar' => [$logger]],       // per-method…
    defaultResultInterceptors: [$logger],         // …or for every method
));

$proxy->bar();
```

```
→ bar({"who":"world"})
← bar returned 'Bar for world!'
```

To **veto** a method body, return a `BodyInterceptionResult` from a body interceptor:

```php
return new BodyInterceptionResult(preventMethodBody: true, returnValue: 'replaced');
```

To **rewrite the result**, assign to the writable context from a result interceptor:

```php
$context->returnValue = 'New value';
```

Whatever is in `$context->returnValue` after the last result interceptor is what the
caller receives.

### Symfony

The bundle (`Proximity\Bundle\ProximityBundle`) wires `ProxyFactory`, `ProxyGenerator`
and both strategies (aliasing `ProxyStrategyInterface` to `EvaluatingStrategy` by
default), and autoconfigures any service implementing an interceptor interface — see
[Strategies & the bundle](docs/guide/strategies-and-bundle.md).

## Known limits

- **By-reference parameters** lose their reference semantics through a proxy
  (arguments are forwarded by value).
- Objects with **uninitialized `readonly` properties** cannot be proxied.

See [State & identity](docs/guide/state-and-identity.md) for details.
