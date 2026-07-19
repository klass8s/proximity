<?php declare(strict_types=1);

namespace Proximity;

use Proximity\MethodsInterception\BodyInterceptorInterface;
use Proximity\MethodsInterception\ResultInterceptorInterface;

final readonly class ProxyOptions
{
    /**
     * @param array<string, list<BodyInterceptorInterface>> $bodyInterceptors method name => interceptors
     * @param array<string, list<ResultInterceptorInterface>> $resultInterceptors method name => interceptors
     * @param list<BodyInterceptorInterface> $defaultBodyInterceptors
     * @param list<ResultInterceptorInterface> $defaultResultInterceptors
     */
    public function __construct(
        public array $bodyInterceptors = [],
        public array $resultInterceptors = [],
        public array $defaultBodyInterceptors = [],
        public array $defaultResultInterceptors = []
    ) {
    }

    /**
     * @return list<BodyInterceptorInterface>
     */
    public function bodyInterceptorsForMethod(string $methodName): array
    {
        return $this->bodyInterceptors[$methodName] ?? $this->defaultBodyInterceptors;
    }

    /**
     * @return list<ResultInterceptorInterface>
     */
    public function resultInterceptorsForMethod(string $methodName): array
    {
        return $this->resultInterceptors[$methodName] ?? $this->defaultResultInterceptors;
    }
}
