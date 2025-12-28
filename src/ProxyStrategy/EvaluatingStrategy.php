<?php declare(strict_types=1);

namespace Proximity\ProxyStrategy;

use Proximity\GeneratedProxy;

class EvaluatingStrategy implements ProxyStrategyInterface
{
    public function apply(GeneratedProxy $proxy): void
    {
        eval($proxy->code);
    }
}
