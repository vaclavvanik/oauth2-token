<?php

declare(strict_types=1);

namespace VaclavVanik\Oauth2Token\Cache;

final class MemoryRepository implements Repository
{
    /** @var array<string, Entry>|null */
    private $cache;

    public function has(Key $key): bool
    {
        return isset($this->cache[$key->key()]);
    }

    public function load(Key $key): Entry
    {
        if ($this->has($key) === false) {
            throw Exception\NotFound::fromKey($key);
        }

        return $this->cache[$key->key()];
    }

    public function save(Key $key, Entry $entry): void
    {
        $this->cache[$key->key()] = $entry;
    }

    public function delete(Key $key): void
    {
        unset($this->cache[$key->key()]);
    }
}
