<?php declare(strict_types=1);

namespace Proximity\Tests\Fixture;

class FakeReadonlyHolder
{
    public readonly string $token;

    public function __construct(?string $token)
    {
        // A null token leaves the readonly property uninitialized on purpose —
        // proxying such an instance is a documented failure mode.
        if (null !== $token) {
            $this->token = $token;
        }
    }

    public function token(): string
    {
        return $this->token;
    }
}
