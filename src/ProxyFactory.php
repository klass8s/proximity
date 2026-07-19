<?php declare(strict_types=1);

namespace Proximity;

use Proximity\ProxyStrategy\ProxyStrategyInterface;

final class ProxyFactory
{
    /** @var array<class-string, class-string> */
    private array $proxyClassesCache = [];
    public function __construct(
        private readonly ProxyGenerator $generator,
        private readonly ProxyStrategyInterface $proxyStrategy
    ) {
    }

    public function proxy(object $object, ?ProxyOptions $options = null): ProxyInterface
    {
        if ($object instanceof ProxyInterface) {
            throw new \InvalidArgumentException('$object is already a proxy');
        }

        if (null === $options) {
            $options = new ProxyOptions([], []);
        }

        $class = new \ReflectionObject($object);
        $proxyClass = $this->proxyClassesCache[$class->getName()] ?? null;
        if (null !== $proxyClass) {
            return $this->instantiate($proxyClass, $object, $options);
        }

        $generatedProxy = $this->generator->generate($class);
        $proxyClass = $generatedProxy->fqcn;

        if (!class_exists($proxyClass, false)) {
            $this->proxyStrategy->apply($generatedProxy);
        }

        if (!class_exists($proxyClass, false)) {
            throw new \LogicException(sprintf('The proxy strategy did not define the generated class "%s".', $proxyClass));
        }

        $this->proxyClassesCache[$class->getName()] = $proxyClass;

        return $this->instantiate($proxyClass, $object, $options);
    }

    /**
     * @param class-string $proxyClass
     */
    private function instantiate(string $proxyClass, object $object, ProxyOptions $options): ProxyInterface
    {
        $proxy = new $proxyClass($object, $options);
        if (!$proxy instanceof ProxyInterface) {
            throw new \LogicException(sprintf('The generated class "%s" does not implement "%s".', $proxyClass, ProxyInterface::class));
        }

        return $proxy;
    }
}
