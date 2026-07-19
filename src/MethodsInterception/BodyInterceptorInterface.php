<?php declare(strict_types=1);

namespace Proximity\MethodsInterception;

use Proximity\ProxyInterface;

interface BodyInterceptorInterface
{
    /**
     * @param array<string, mixed> $methodArgs named argument map: parameter name => value, declared defaults applied
     */
    public function beforeMethodBody(ProxyInterface $proxy, object $object, string $methodName, array $methodArgs): BodyInterceptionResult|null;
}
