<?php declare(strict_types=1);

namespace Proximity\MethodsInterception;

use Proximity\ProxyInterface;

interface BodyInterceptorInterface
{
    public function beforeMethodBody(ProxyInterface $proxy, object $object, string $methodName, array $methodArgs): BodyInterceptionResult|null;
}
