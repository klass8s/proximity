<?php declare(strict_types=1);

namespace Proximity\Tests\Bundle;

use Proximity\Bundle\ProximityBundle;
use Proximity\ProxyFactory;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

final class ProximityTestKernel extends Kernel
{
    public function __construct(private readonly string $tempProjectDir)
    {
        parent::__construct('test', true);
    }

    public function registerBundles(): iterable
    {
        return [new FrameworkBundle(), new ProximityBundle()];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(static function (ContainerBuilder $container): void {
            $container->loadFromExtension('framework', [
                'secret' => 'proximity-test',
                'test' => true,
                'http_method_override' => false,
                'handle_all_throwables' => true,
                'php_errors' => ['log' => true],
            ]);

            // Nothing references ProxyFactory in this minimal kernel, so the
            // compiler would remove it before the test container can see it.
            $container->setAlias('proximity.test.proxy_factory', ProxyFactory::class)->setPublic(true);
        });
    }

    public function getProjectDir(): string
    {
        return $this->tempProjectDir;
    }

    public function getCacheDir(): string
    {
        return $this->tempProjectDir.'/cache';
    }

    public function getLogDir(): string
    {
        return $this->tempProjectDir.'/log';
    }
}
