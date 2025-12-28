<?php declare(strict_types=1);

namespace Proximity\ProxyStrategy;

use Proximity\GeneratedProxy;

readonly class SaveToFileStrategy implements ProxyStrategyInterface
{
    public function __construct(private string $proxyClassesDirectory)
    {
    }

    public function apply(GeneratedProxy $proxy): void
    {
        $relativePath = str_replace('\\', '/', $proxy->originalClass->getNamespaceName());
        $dir = $this->proxyClassesDirectory.'/'.$relativePath;
        if (!file_exists($dir)) {
            mkdir($dir, 0o777, true);
        }
        $code = '<?php'.PHP_EOL.$proxy->code;
        $fullPath = $dir.'/'.$proxy->originalClass->getShortName().'.php';
        file_put_contents($fullPath, $code);

        require_once($fullPath);
    }
}
