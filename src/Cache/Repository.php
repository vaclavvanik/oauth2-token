<?php

declare(strict_types=1);

namespace VaclavVanik\Oauth2Token\Cache;

interface Repository
{
    /** @throws Exception\Runtime */
    public function has(Key $key): bool;

    /**
     * @throws Exception\NotFound
     * @throws Exception\Runtime
     */
    public function load(Key $key): Entry;

    /** @throws Exception\Runtime */
    public function save(Key $key, Entry $entry): void;

    /**
     * Remove the entry for the key. A no-op when the key is not stored.
     *
     * @throws Exception\Runtime
     */
    public function delete(Key $key): void;
}
