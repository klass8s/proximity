<?php declare(strict_types=1);

namespace Proximity\Tests\Bundle;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Proximity\Bundle\DependencyInjection\Compiler\InterceptorsPass;
use Proximity\MethodsInterception\BodyInterceptorInterface;
use Proximity\MethodsInterception\ResultInterceptorInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[CoversClass(InterceptorsPass::class)]
final class InterceptorsPassTest extends TestCase
{
    public function testInterceptorInterfacesAreAutoconfiguredWithTheirTags(): void
    {
        $container = new ContainerBuilder();
        new InterceptorsPass()->process($container);

        $autoconfigured = $container->getAutoconfiguredInstanceof();

        self::assertArrayHasKey(BodyInterceptorInterface::class, $autoconfigured);
        self::assertArrayHasKey(InterceptorsPass::BODY_INTERCEPTOR_TAG, $autoconfigured[BodyInterceptorInterface::class]->getTags());

        self::assertArrayHasKey(ResultInterceptorInterface::class, $autoconfigured);
        self::assertArrayHasKey(InterceptorsPass::RESULT_INTERCEPTOR_TAG, $autoconfigured[ResultInterceptorInterface::class]->getTags());
    }
}
