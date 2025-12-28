<?php declare(strict_types=1);

namespace Proximity\MethodsInterception;

class InterceptionContext
{
    public function __construct(public mixed $returnValue = null)
    {
    }
}
