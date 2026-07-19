<?php declare(strict_types=1);

namespace Proximity\MethodsInterception;

use Proximity\ProxyInterface;

interface ResultInterceptorInterface
{
    /**
     * @param array<string, mixed> $methodArgs named argument map: parameter name => value, declared defaults applied
     */
    public function afterMethodBody(ProxyInterface $proxy, object $object, string $methodName, array $methodArgs, InterceptionContext $context): void;
}
