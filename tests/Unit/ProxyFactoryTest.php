<?php declare(strict_types=1);

namespace Proximity\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Proximity\ProxyFactory;
use Proximity\ProxyGenerator;
use Proximity\ProxyInterface;
use Proximity\ProxyOptions;
use Proximity\ProxyStrategy\EvaluatingStrategy;
use Proximity\Tests\Fixture\CallRecorder;
use Proximity\Tests\Fixture\FakeCalculator;
use Proximity\Tests\Fixture\FakeFinalService;
use Proximity\Tests\Fixture\FakeService;
use Proximity\Tests\Fixture\RecordingBodyInterceptor;

#[CoversClass(ProxyFactory::class)]
#[CoversClass(ProxyGenerator::class)]
final class ProxyFactoryTest extends TestCase
{
    private ProxyFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new ProxyFactory(new ProxyGenerator(), new EvaluatingStrategy());
    }

    public function testProxyIsASubclassAndCarriesTheMarkerInterface(): void
    {
        $proxy = $this->factory->proxy(new FakeService());

        self::assertInstanceOf(FakeService::class, $proxy);
        self::assertInstanceOf(ProxyInterface::class, $proxy);
    }

    public function testProxyClassNameFollowsTheDocumentedShape(): void
    {
        $proxy = $this->factory->proxy(new FakeService());

        self::assertSame(
            ProxyGenerator::DEFAULT_NAMESPACE.'\\Proximity\\Tests\\Fixture\\FakeServiceProxy',
            $proxy::class,
        );
    }

    public function testProxyAndOriginalAreDistinctObjects(): void
    {
        $original = new FakeService();
        $proxy = $this->factory->proxy($original);

        self::assertNotSame($original, $proxy);
        self::assertSame(FakeService::class, new \ReflectionClass($proxy)->getParentClass()?->getName());
    }

    public function testProxyingAProxyIsRefused(): void
    {
        $proxy = $this->factory->proxy(new FakeService());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('already a proxy');
        $this->factory->proxy($proxy);
    }

    public function testProxyingAFinalClassIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('final');
        $this->factory->proxy(new FakeFinalService());
    }

    public function testGeneratedClassIsReusedAcrossProxiesWithIndependentOptions(): void
    {
        $recorder = new CallRecorder();
        $intercepted = $this->factory->proxy(new FakeCalculator(), new ProxyOptions(
            defaultBodyInterceptors: [new RecordingBodyInterceptor($recorder, 'only')],
        ));
        $plain = $this->factory->proxy(new FakeCalculator());

        self::assertSame($intercepted::class, $plain::class);
        self::assertNotSame($intercepted, $plain);

        self::assertInstanceOf(FakeCalculator::class, $plain);
        $plain->add(1);
        self::assertSame([], $recorder->calls);

        self::assertInstanceOf(FakeCalculator::class, $intercepted);
        $intercepted->add(1);
        self::assertSame(['only:add'], $recorder->calls);
    }
}
