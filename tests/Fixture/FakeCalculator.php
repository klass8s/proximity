<?php declare(strict_types=1);

namespace Proximity\Tests\Fixture;

class FakeCalculator
{
    public bool $bodyRan = false;

    public function add(int $a, int $b = 10): int
    {
        $this->bodyRan = true;

        return $a + $b;
    }

    public function describe(?string $label = null): string
    {
        return $label ?? 'nothing';
    }

    public function reset(): void
    {
        $this->bodyRan = false;
    }

    public function bump(int &$value): void
    {
        ++$value;
    }

    final public function finalAnswer(): int
    {
        return 42;
    }

    public static function statically(): string
    {
        return 'static';
    }
}
