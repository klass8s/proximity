<?php declare(strict_types=1);

namespace Proximity\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Proximity\MethodsInterception\BodyInterceptionResult;

#[CoversClass(BodyInterceptionResult::class)]
final class BodyInterceptionResultTest extends TestCase
{
    public function testReturnValueWithoutPreventionIsRejected(): void
    {
        $this->expectException(\LogicException::class);
        new BodyInterceptionResult(preventMethodBody: false, returnValue: 'anything');
    }

    public function testPreventionMayCarryAReturnValue(): void
    {
        $result = new BodyInterceptionResult(preventMethodBody: true, returnValue: 'substitute');

        self::assertTrue($result->preventMethodBody);
        self::assertSame('substitute', $result->returnValue);
    }

    public function testProceedIsTheDefault(): void
    {
        $result = new BodyInterceptionResult();

        self::assertFalse($result->preventMethodBody);
        self::assertNull($result->returnValue);
    }
}
