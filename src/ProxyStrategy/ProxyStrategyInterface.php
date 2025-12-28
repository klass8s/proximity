<?php declare(strict_types=1);

namespace Proximity\ProxyStrategy;
use Proximity\GeneratedProxy;

interface ProxyStrategyInterface
{
    public function apply(GeneratedProxy $proxy): void;
}
