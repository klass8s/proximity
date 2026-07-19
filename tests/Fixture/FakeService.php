<?php declare(strict_types=1);

namespace Proximity\Tests\Fixture;

class FakeService extends FakeParentService
{
    public string $name = 'initial';
    protected int $counter = 0;
    private string $secret = 'own-initial';

    public function secret(): string
    {
        return $this->secret;
    }

    public function changeSecret(string $value): void
    {
        $this->secret = $value;
    }

    public function counter(): int
    {
        return $this->counter;
    }

    public function increment(): void
    {
        ++$this->counter;
    }
}
