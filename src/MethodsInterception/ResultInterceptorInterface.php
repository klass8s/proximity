<?php declare(strict_types=1);

namespace Proximity\MethodsInterception;

use Proximity\ProxyInterface;

interface ResultInterceptorInterface
{
    public function afterMethodBody(ProxyInterface $proxy, object $object, string $methodName, array $methodArgs, InterceptionContext $context): void;
}
