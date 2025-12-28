<?php declare(strict_types=1);

namespace Proximity\Bundle\DependencyInjection\Compiler;

use Proximity\MethodsInterception\BodyInterceptorInterface;
use Proximity\MethodsInterception\ResultInterceptorInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class InterceptorsPass implements CompilerPassInterface
{
    public const string BODY_INTERCEPTOR_TAG = 'proximity.interceptor.body';
    public const string RESULT_INTERCEPTOR_TAG = 'proximity.interceptor.result';


    public function process(ContainerBuilder $container): void
    {
        $container
            ->registerForAutoconfiguration(BodyInterceptorInterface::class)
            ->addTag(self::BODY_INTERCEPTOR_TAG);

        $container
            ->registerForAutoconfiguration(ResultInterceptorInterface::class)
            ->addTag(self::RESULT_INTERCEPTOR_TAG);
    }
}
