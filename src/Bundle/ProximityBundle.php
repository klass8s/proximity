<?php declare(strict_types=1);

namespace Proximity\Bundle;

use Proximity\Bundle\DependencyInjection\Compiler\InterceptorsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class ProximityBundle extends AbstractBundle
{
    public function boot(): void
    {
        $proxiesDir = $this->container?->getParameter('proximity.proxy_classes_dir');
        if (!is_string($proxiesDir) || '' === $proxiesDir) {
            throw new \LogicException('The "proximity.proxy_classes_dir" parameter must be a non-empty string.');
        }
        if (!file_exists($proxiesDir)) {
            mkdir($proxiesDir, 0o777, true);
        }
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(pass: new InterceptorsPass(), priority: 500);
    }

    /**
     * @param mixed[] $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../../config/proximity.yaml');
    }
}
