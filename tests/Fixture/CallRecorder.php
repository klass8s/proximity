<?php declare(strict_types=1);

namespace Proximity\Tests\Fixture;

final class CallRecorder
{
    /** @var list<string> */
    public array $calls = [];

    /** @var array<string, mixed> */
    public array $lastArgs = [];

    public function record(string $label): void
    {
        $this->calls[] = $label;
    }
}
