<?php declare(strict_types=1);

namespace Proximity\Tests\Fixture;

use Proximity\MethodsInterception\BodyInterceptionResult;
use Proximity\MethodsInterception\BodyInterceptorInterface;
use Proximity\ProxyInterface;

final class RecordingBodyInterceptor implements BodyInterceptorInterface
{
    public function __construct(
        private readonly CallRecorder $recorder,
        private readonly string $label,
        private readonly ?BodyInterceptionResult $result = null,
    ) {
    }

    public function beforeMethodBody(ProxyInterface $proxy, object $object, string $methodName, array $methodArgs): BodyInterceptionResult|null
    {
        $this->recorder->record($this->label.':'.$methodName);
        $this->recorder->lastArgs = $methodArgs;

        return $this->result;
    }
}
