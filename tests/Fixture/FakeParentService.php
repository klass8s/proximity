<?php declare(strict_types=1);

namespace Proximity\Tests\Fixture;

class FakeParentService
{
    private string $parentSecret = 'parent-initial';

    public function parentSecret(): string
    {
        return $this->parentSecret;
    }

    public function renameParentSecret(string $value): void
    {
        $this->parentSecret = $value;
    }
}
