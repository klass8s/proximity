<?php declare(strict_types=1);

namespace Proximity\Tests\Fixture;

final class FakeFinalService
{
    public function answer(): int
    {
        return 42;
    }
}
