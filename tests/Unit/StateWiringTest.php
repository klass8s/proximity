<?php declare(strict_types=1);

namespace Proximity\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Proximity\ProxyFactory;
use Proximity\ProxyGenerator;
use Proximity\ProxyStrategy\EvaluatingStrategy;
use Proximity\Tests\Fixture\FakeCalculator;
use Proximity\Tests\Fixture\FakeHookedGreeter;
use Proximity\Tests\Fixture\FakeHookedTagHolder;
use Proximity\Tests\Fixture\FakeReadonlyHolder;
use Proximity\Tests\Fixture\FakeService;

#[CoversClass(ProxyGenerator::class)]
final class StateWiringTest extends TestCase
{
    private ProxyFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new ProxyFactory(new ProxyGenerator(), new EvaluatingStrategy());
    }

    public function testPublicPropertyIsBoundByReferenceInBothDirections(): void
    {
        $original = new FakeService();
        $proxy = $this->factory->proxy($original);
        self::assertInstanceOf(FakeService::class, $proxy);

        $original->name = 'changed-on-original';
        self::assertSame('changed-on-original', $proxy->name);

        $proxy->name = 'changed-on-proxy';
        self::assertSame('changed-on-proxy', $original->name);
    }

    public function testProtectedPropertyStaysLiveThroughProxyMethods(): void
    {
        $original = new FakeService();
        $proxy = $this->factory->proxy($original);
        self::assertInstanceOf(FakeService::class, $proxy);

        $original->increment();
        $proxy->increment();

        // Both writes hit the same underlying property.
        self::assertSame(2, $original->counter());
        self::assertSame(2, $proxy->counter());
    }

    public function testPrivatePropertyOfTheClassIsBoundByReference(): void
    {
        $original = new FakeService();
        $proxy = $this->factory->proxy($original);
        self::assertInstanceOf(FakeService::class, $proxy);

        $original->changeSecret('rotated');
        self::assertSame('rotated', $proxy->secret());

        $proxy->changeSecret('rotated-again');
        self::assertSame('rotated-again', $original->secret());
    }

    public function testPrivatePropertyOfAParentClassIsBoundByReference(): void
    {
        $original = new FakeService();
        $proxy = $this->factory->proxy($original);
        self::assertInstanceOf(FakeService::class, $proxy);

        $original->renameParentSecret('parent-rotated');
        self::assertSame('parent-rotated', $proxy->parentSecret());
    }

    public function testReadonlyPropertyIsCopiedAtConstruction(): void
    {
        $original = new FakeReadonlyHolder('token-1');
        $proxy = $this->factory->proxy($original);
        self::assertInstanceOf(FakeReadonlyHolder::class, $proxy);

        self::assertSame('token-1', $proxy->token());
        self::assertSame('token-1', $proxy->token);
    }

    public function testUninitializedReadonlyPropertyFailsProxyConstruction(): void
    {
        // Documented limit: the constructor copies readonly values eagerly,
        // so an uninitialized readonly property throws at that read.
        $original = new FakeReadonlyHolder(null);

        $this->expectException(\Error::class);
        $this->factory->proxy($original);
    }

    public function testHookedPropertyIsInheritedAndReadsLiveState(): void
    {
        $original = new FakeHookedGreeter();
        $proxy = $this->factory->proxy($original);
        self::assertInstanceOf(FakeHookedGreeter::class, $proxy);

        self::assertSame('hello, world', $proxy->greeting);

        $original->name = 'petr';
        self::assertSame('hello, petr', $proxy->greeting);
    }

    public function testBackedHookedPropertySetBeforeProxyingIsCarriedOver(): void
    {
        $original = new FakeHookedTagHolder();
        $original->setTag('pre-proxy-value');

        $proxy = $this->factory->proxy($original);
        self::assertInstanceOf(FakeHookedTagHolder::class, $proxy);

        self::assertSame('pre-proxy-value', $proxy->tag);
    }

    public function testUninitializedBackedHookedPropertyKeepsItsGuardSemantics(): void
    {
        $original = new FakeHookedTagHolder();

        $proxy = $this->factory->proxy($original);
        self::assertInstanceOf(FakeHookedTagHolder::class, $proxy);

        // The guard's own exception, not a typed-property initialization Error.
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Tag is not set yet.');
        /** @phpstan-ignore expr.resultUnused */
        $proxy->tag;
    }

    public function testBackedHookedPropertyDefaultValueSurvivesProxying(): void
    {
        $original = new FakeHookedTagHolder();
        $proxy = $this->factory->proxy($original);
        self::assertInstanceOf(FakeHookedTagHolder::class, $proxy);

        self::assertSame([], $proxy->bag);

        $proxy->setBag(['a' => 1]);
        self::assertSame(['a' => 1], $proxy->bag);
    }

    public function testByReferenceParametersLoseReferenceSemanticsThroughTheProxy(): void
    {
        // Documented limit: generated methods forward via func_get_args(),
        // which drops by-ref semantics. This asserts the CURRENT behavior so a
        // future fix has to update this test consciously.
        $original = new FakeCalculator();
        $proxy = $this->factory->proxy($original);
        self::assertInstanceOf(FakeCalculator::class, $proxy);

        $direct = 1;
        $original->bump($direct);
        self::assertSame(2, $direct);

        $throughProxy = 1;
        $proxy->bump($throughProxy);
        self::assertSame(1, $throughProxy);
    }
}
