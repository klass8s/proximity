<?php declare(strict_types=1);

namespace Proximity\Tests\Fixture;

use Proximity\MethodsInterception\InterceptionContext;
use Proximity\MethodsInterception\ResultInterceptorInterface;
use Proximity\ProxyInterface;

final class RecordingResultInterceptor implements ResultInterceptorInterface
{
    /**
     * @param (\Closure(mixed): mixed)|null $rewrite
     */
    public function __construct(
        private readonly CallRecorder $recorder,
        private readonly string $label,
        private readonly ?\Closure $rewrite = null,
    ) {
    }

    public function afterMethodBody(ProxyInterface $proxy, object $object, string $methodName, array $methodArgs, InterceptionContext $context): void
    {
        $this->recorder->record($this->label.':'.$methodName);

        if (null !== $this->rewrite) {
            $context->returnValue = ($this->rewrite)($context->returnValue);
        }
    }
}
