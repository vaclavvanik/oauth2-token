<?php

declare(strict_types=1);

namespace VaclavVanikTest\Oauth2Token\Support;

use RuntimeException;
use VaclavVanik\Oauth2Token\Cache\Entry;
use VaclavVanik\Oauth2Token\Cache\Exception\NotFound;
use VaclavVanik\Oauth2Token\Cache\Key;
use VaclavVanik\Oauth2Token\Cache\Repository;

final class ThrowingRepository implements Repository
{
    /** @var bool */
    private $found;

    public function __construct(bool $found)
    {
        $this->found = $found;
    }

    public function has(Key $key): bool
    {
        return $this->found;
    }

    public function load(Key $key): Entry
    {
        if ($this->found === false) {
            throw NotFound::fromKey($key);
        }

        throw new RuntimeException('load failed');
    }

    public function save(Key $key, Entry $entry): void
    {
        throw new RuntimeException('save failed');
    }

    public function delete(Key $key): void
    {
        throw new RuntimeException('delete failed');
    }
}
