<?php declare(strict_types=1);

namespace Proximity\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Proximity\MethodsInterception\BodyInterceptionResult;
use Proximity\ProxyFactory;
use Proximity\ProxyGenerator;
use Proximity\ProxyOptions;
use Proximity\ProxyStrategy\EvaluatingStrategy;
use Proximity\Tests\Fixture\CallRecorder;
use Proximity\Tests\Fixture\FakeCalculator;
use Proximity\Tests\Fixture\RecordingBodyInterceptor;
use Proximity\Tests\Fixture\RecordingResultInterceptor;

#[CoversClass(ProxyGenerator::class)]
#[CoversClass(ProxyOptions::class)]
final class InterceptionPipelineTest extends TestCase
{
    private ProxyFactory $factory;
    private CallRecorder $recorder;

    protected function setUp(): void
    {
        $this->factory = new ProxyFactory(new ProxyGenerator(), new EvaluatingStrategy());
        $this->recorder = new CallRecorder();
    }

    public function testBodyInterceptorsRunInOrderThenBodyThenResultInterceptorsInOrder(): void
    {
        $proxy = $this->proxyCalculator(new ProxyOptions(
            defaultBodyInterceptors: [
                new RecordingBodyInterceptor($this->recorder, 'body-1'),
                new RecordingBodyInterceptor($this->recorder, 'body-2'),
            ],
            defaultResultInterceptors: [
                new RecordingResultInterceptor($this->recorder, 'result-1'),
                new RecordingResultInterceptor($this->recorder, 'result-2'),
            ],
        ));

        self::assertSame(3, $proxy->add(1, 2));
        self::assertSame(['body-1:add', 'body-2:add', 'result-1:add', 'result-2:add'], $this->recorder->calls);
    }

    public function testInterceptorsReceiveTheNamedArgumentMapWithDeclaredDefaultsApplied(): void
    {
        $proxy = $this->proxyCalculator(new ProxyOptions(
            defaultBodyInterceptors: [new RecordingBodyInterceptor($this->recorder, 'body')],
        ));

        self::assertSame(11, $proxy->add(1));
        self::assertSame(['a' => 1, 'b' => 10], $this->recorder->lastArgs);
    }

    public function testPreventionSkipsBodyLaterBodyInterceptorsAndAllResultInterceptors(): void
    {
        $original = new FakeCalculator();
        $proxy = $this->proxyCalculator(new ProxyOptions(
            defaultBodyInterceptors: [
                new RecordingBodyInterceptor($this->recorder, 'preventing', new BodyInterceptionResult(preventMethodBody: true, returnValue: 99)),
                new RecordingBodyInterceptor($this->recorder, 'unreached'),
            ],
            defaultResultInterceptors: [new RecordingResultInterceptor($this->recorder, 'unreached-result')],
        ), $original);

        self::assertSame(99, $proxy->add(1, 2));
        self::assertFalse($original->bodyRan, 'prevention must skip the method body');
        self::assertSame(['preventing:add'], $this->recorder->calls);
    }

    public function testPreventionOnAVoidMethodReturnsNothingAndSkipsTheBody(): void
    {
        $original = new FakeCalculator();
        $original->bodyRan = true;
        $proxy = $this->proxyCalculator(new ProxyOptions(
            defaultBodyInterceptors: [
                new RecordingBodyInterceptor($this->recorder, 'preventing', new BodyInterceptionResult(preventMethodBody: true)),
            ],
        ), $original);

        $proxy->reset();
        self::assertTrue($original->bodyRan, 'reset() body must not run when prevented');
    }

    public function testResultInterceptorsChainOnTheSharedContext(): void
    {
        $proxy = $this->proxyCalculator(new ProxyOptions(
            defaultResultInterceptors: [
                new RecordingResultInterceptor($this->recorder, 'double', static fn (mixed $value): mixed => \is_int($value) ? $value * 2 : $value),
                new RecordingResultInterceptor($this->recorder, 'plus-one', static fn (mixed $value): mixed => \is_int($value) ? $value + 1 : $value),
            ],
        ));

        // (1 + 2) * 2 + 1 — the second interceptor sees the first one's rewrite.
        self::assertSame(7, $proxy->add(1, 2));
    }

    public function testPerMethodRoutingReplacesTheDefaultsInsteadOfMerging(): void
    {
        $proxy = $this->proxyCalculator(new ProxyOptions(
            bodyInterceptors: ['add' => [new RecordingBodyInterceptor($this->recorder, 'add-specific')]],
            defaultBodyInterceptors: [new RecordingBodyInterceptor($this->recorder, 'default')],
        ));

        $proxy->add(1);
        self::assertSame(['add-specific:add'], $this->recorder->calls, 'defaults must not run for a routed method');

        $this->recorder->calls = [];
        $proxy->describe('x');
        self::assertSame(['default:describe'], $this->recorder->calls, 'unrouted methods fall back to defaults');
    }

    public function testFinalAndStaticMethodsBypassInterception(): void
    {
        $proxy = $this->proxyCalculator(new ProxyOptions(
            defaultBodyInterceptors: [new RecordingBodyInterceptor($this->recorder, 'body')],
        ));

        self::assertSame(42, $proxy->finalAnswer());
        self::assertSame('static', $proxy::statically());
        self::assertSame([], $this->recorder->calls);
    }

    public function testNullableParameterRoundTripsThroughTheGeneratedSignature(): void
    {
        $proxy = $this->proxyCalculator(new ProxyOptions(
            defaultBodyInterceptors: [new RecordingBodyInterceptor($this->recorder, 'body')],
        ));

        self::assertSame('nothing', $proxy->describe(null));
        self::assertSame(['label' => null], $this->recorder->lastArgs);
    }

    private function proxyCalculator(ProxyOptions $options, ?FakeCalculator $original = null): FakeCalculator
    {
        $proxy = $this->factory->proxy($original ?? new FakeCalculator(), $options);
        \assert($proxy instanceof FakeCalculator);

        return $proxy;
    }
}
