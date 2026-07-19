<?php declare(strict_types=1);

namespace Proximity\Tests\Fixture;

class FakeHookedGreeter
{
    public string $name = 'world';

    public string $greeting {
        get => 'hello, '.$this->name;
    }
}
