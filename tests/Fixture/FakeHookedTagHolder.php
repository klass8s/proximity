<?php declare(strict_types=1);

namespace Proximity\Tests\Fixture;

/**
 * A backed hooked property guarded by a plain flag — the write-once shape used by
 * consumers (e.g. Klass8s manifest templates and config): the flag is bound by
 * reference, the backing value must be carried over at proxy construction.
 */
class FakeHookedTagHolder
{
    protected bool $tagSet = false;

    public protected(set) string $tag {
        get {
            if (!$this->tagSet) {
                throw new \RuntimeException('Tag is not set yet.');
            }

            return $this->tag;
        }
        set {
            if ($this->tagSet) {
                throw new \RuntimeException('Tag is already set.');
            }

            $this->tag = $value;
            $this->tagSet = true;
        }
    }

    public protected(set) array $bag = [] {
        get => $this->bag;
        set {
            $this->bag = $value;
        }
    }

    public function setTag(string $tag): void
    {
        $this->tag = $tag;
    }

    public function setBag(array $bag): void
    {
        $this->bag = $bag;
    }
}
