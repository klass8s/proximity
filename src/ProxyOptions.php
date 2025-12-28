<?php declare(strict_types=1);

namespace Proximity;

use Proximity\MethodsInterception\BodyInterceptorInterface;
use Proximity\MethodsInterception\ResultInterceptorInterface;

final readonly class ProxyOptions
{
    /**
     * @param BodyInterceptorInterface[][] $bodyInterceptors
     * @param ResultInterceptorInterface[][] $resultInterceptors
     * @param BodyInterceptorInterface[] $defaultBodyInterceptors
     * @param ResultInterceptorInterface[] $defaultResultInterceptors
     */
    public function __construct(
        public array $bodyInterceptors = [],
        public array $resultInterceptors = [],
        public array $defaultBodyInterceptors = [],
        public array $defaultResultInterceptors = []
    ) {
    }

    public function bodyInterceptorsForMethod(string $methodName): array
    {
        return $this->bodyInterceptors[$methodName] ?? $this->defaultBodyInterceptors;
    }

    public function resultInterceptorsForMethod(string $methodName): array
    {
        return $this->resultInterceptors[$methodName] ?? $this->defaultResultInterceptors;
    }
}
