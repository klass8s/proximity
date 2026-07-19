<?php declare(strict_types=1);

namespace Proximity\Tests\Bundle;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Proximity\Bundle\ProximityBundle;
use Proximity\ProxyFactory;
use Proximity\ProxyInterface;
use Proximity\Tests\Fixture\FakeService;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[CoversClass(ProximityBundle::class)]
final class ProximityBundleTest extends TestCase
{
    private string $tempDir;
    private ?ProximityTestKernel $kernel = null;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/proximity-kernel-'.bin2hex(random_bytes(6));
        mkdir($this->tempDir, 0o777, true);
    }

    protected function tearDown(): void
    {
        $this->kernel?->shutdown();

        // FrameworkBundle registers an exception handler at boot; drop it so
        // PHPUnit does not flag the kernel tests as risky.
        restore_exception_handler();

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->tempDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $file) {
            \assert($file instanceof \SplFileInfo);
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->tempDir);
    }

    public function testBootedKernelWiresAWorkingProxyFactoryWithTheEvaluatingDefault(): void
    {
        $container = $this->bootedTestContainer();

        $factory = $container->get('proximity.test.proxy_factory');
        self::assertInstanceOf(ProxyFactory::class, $factory);

        // End-to-end through the DI wiring (EvaluatingStrategy is the default alias).
        $proxy = $factory->proxy(new FakeService());
        self::assertInstanceOf(ProxyInterface::class, $proxy);
        self::assertInstanceOf(FakeService::class, $proxy);
    }

    public function testBootCreatesTheProxyClassesDirectory(): void
    {
        $this->bootedTestContainer();

        self::assertDirectoryExists($this->tempDir.'/var/proxy');
    }

    private function bootedTestContainer(): ContainerInterface
    {
        $this->kernel = new ProximityTestKernel($this->tempDir);
        $this->kernel->boot();

        $testContainer = $this->kernel->getContainer()->get('test.service_container');
        \assert($testContainer instanceof ContainerInterface);

        return $testContainer;
    }
}
