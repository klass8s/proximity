<?php declare(strict_types=1);

namespace Proximity\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Proximity\ProxyFactory;
use Proximity\ProxyGenerator;
use Proximity\ProxyStrategy\EvaluatingStrategy;
use Proximity\ProxyStrategy\SaveToFileStrategy;
use Proximity\Tests\Fixture\FakeHookedGreeter;
use Proximity\Tests\Fixture\FakeService;

#[CoversClass(EvaluatingStrategy::class)]
#[CoversClass(SaveToFileStrategy::class)]
final class ProxyStrategyTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/proximity-test-'.bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
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
    }

    public function testEvaluatingStrategyDefinesTheProxyClassInProcess(): void
    {
        // A distinct generator namespace guarantees this test defines the class itself.
        $generator = new ProxyGenerator('Proximity\\ProxyEvalTest');
        $generated = $generator->generate(new \ReflectionClass(FakeService::class));

        self::assertFalse(class_exists($generated->fqcn, false));
        new EvaluatingStrategy()->apply($generated);
        self::assertTrue(class_exists($generated->fqcn, false));
    }

    public function testSaveToFileStrategyWritesRequiresAndYieldsAWorkingProxy(): void
    {
        $generator = new ProxyGenerator('Proximity\\ProxyFileTest');
        $factory = new ProxyFactory($generator, new SaveToFileStrategy($this->tempDir));

        $original = new FakeHookedGreeter();
        $proxy = $factory->proxy($original);
        self::assertInstanceOf(FakeHookedGreeter::class, $proxy);
        $original->name = 'file';
        self::assertSame('hello, file', $proxy->greeting);

        // File lands at <dir>/<original namespace path>/<OriginalShortName>.php.
        $expectedFile = $this->tempDir.'/Proximity/Tests/Fixture/FakeHookedGreeter.php';
        self::assertFileExists($expectedFile);

        $code = (string) file_get_contents($expectedFile);
        self::assertStringStartsWith('<?php', $code);
        self::assertStringContainsString('class FakeHookedGreeterProxy', $code);
    }
}
